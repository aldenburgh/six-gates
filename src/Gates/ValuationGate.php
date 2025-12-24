<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

class ValuationGate implements GateInterface
{
    private array $thresholds;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        $ratios = $provider->getRatios($ticker, 5);
        $estimates = $provider->getAnalystEstimates($ticker);

        if (empty($ratios)) {
            return new GateResult('gate_4', false, [], 'Insufficient data');
        }

        // PEG Ratio
        $latestPeg = $ratios[0]['priceToEarningsGrowthRatio'] ?? $ratios[0]['pegRatio'] ?? 0;

        // PEGY Ratio: P/E / (Growth + Yield)
        // We need Dividend Yield
        $divYield = $ratios[0]['dividendYield'] ?? 0;

        // If PEG is given, we can back out Growth? PEG = PE / Growth -> Growth = PE / PEG
        // Or strictly use analyst growth estimates.
        // Let's use the provided PEG for simplicity if it exists, otherwise skip or approximate.
        // PEGY = PEG * Growth / (Growth + Yield) ??? No.
        // PEGY = PE / (GrowthRate + DividendYield)
        // If we have PEG, PEG = PE/Growth. So Growth = PE/PEG.

        $pe = $ratios[0]['priceToEarningsRatio'] ?? 0;
        $growthRate = ($latestPeg && $latestPeg != 0) ? ($pe / $latestPeg) * 100 : 10; // estimate 10% if missing fallback
        // growthRate is typically an integer percentage in formula denominators (e.g. 15 for 15%), 
        // but PEG definitions vary (sometimes decimal). FMP usually normalized.
        // Let's assume FMP PEG is standard.

        // Let's re-calculate PEGY carefully.
        // If PEG = 1.0, and PE = 20, Growth = 20.
        // Yield = 2% (0.02).
        // PEGY = 20 / (20 + 2) = 20 / 22 = 0.90.

        $pegy = null;
        if ($latestPeg) {
            $growthKw = $latestPeg != 0 ? $pe / $latestPeg : 0; // This is 'Growth' as a number (e.g. 20)
            if ($growthKw + ($divYield * 100) > 0) {
                $pegy = $pe / ($growthKw + ($divYield * 100));
            }
        }

        // EV/FCF
        // FMP doesn't provide EV/FCF directly in ratios usually, it has EV and we have FCF.
        // Or 'enterpriseValueMultiple' is EV/EBITDA.
        // Let's calculate EV/FCF if possible, or use P/FCF as proxy if EV missing.
        // Ratios has priceToFreeCashFlowRatio (singular Flow)
        $pFcf = $ratios[0]['priceToFreeCashFlowRatio'] ?? $ratios[0]['priceToFreeCashFlowsRatio'] ?? 0;

        // Historical Percentile
        // Retrieve last 5y P/E or P/FCF to see where current sits.
        $historicalPes = array_column($ratios, 'priceToEarningsRatio');
        $currentPe = $pe;
        $minPe = min($historicalPes);
        $maxPe = max($historicalPes);

        $percentile = 0.5; // Default middle
        if ($maxPe != $minPe) {
            $percentile = ($currentPe - $minPe) / ($maxPe - $minPe);
        }

        $metrics = [
            'peg' => $latestPeg,
            'pegy' => $pegy,
            'p_fcf' => $pFcf,
            'valuation_percentile' => $percentile
        ];

        // This gate does not 'Kill' usually, but 'Fails' if overvalued.
        // "Fail = wait for better price, not kill"
        $reasons = [];
        if ($latestPeg > $this->thresholds['peg_acceptable']) {
            $reasons[] = sprintf("PEG (%.2f) > %.2f", $latestPeg, $this->thresholds['peg_acceptable']);
        }

        // We only return False if it's egregiously expensive? 
        // Or if it fails to meet 'Reasonable' criteria.
        // Let's strict fail if > acceptable.

        return new GateResult(
            'gate_4',
            empty($reasons),
            $metrics,
            !empty($reasons) ? implode('; ', $reasons) : null,
            ['action' => empty($reasons) ? 'PROCEED' : 'WAIT']
        );
    }
}
