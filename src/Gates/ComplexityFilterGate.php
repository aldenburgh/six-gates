<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Utils\Statistics;

class ComplexityFilterGate implements GateInterface
{
    public function __construct(private array $thresholds)
    {
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        // 1. Sector/Industry Checks
        $profile = $provider->getQuote($ticker)[0] ?? []; // Often profile endpoint better but quote has name
        // We might need 'profile' endpoint for better sector data? 
        // Provider defines getQuote, lets assume it has basic data or we add getProfile.
        // FMP Quote usually has 'sector' ? No, usually just basic price.
        // Let's use the 'profile' endpoint logic if available, or fetch it.
        // Since we didn't implement 'getCompanyProfile' in Provider yet, we might rely on the LLM or check if Quote has it. 
        // Check docs: Quote has "name", "exchange". Usually not Sector.
        // Let's add getCompanyProfile to provider later. For now, we mock or skip sector specific strict checks if missing.

        // Actually, FMP 'quote' sometimes is light. 'profile' is robust.
        // Let's assume we can fetch profile using 'profile' endpoint.

        // 2. Earnings Predictability
        // Variance of EPS over 5 years.
        $income = $provider->getIncomeStatement($ticker, 5);
        $epsList = array_map(fn($i) => $i['eps'] ?? 0, $income);

        // Calculate Coefficient of Variation (StdDev / Mean)
        $stdDev = Statistics::standardDeviation($epsList);
        $mean = !empty($epsList) ? array_sum($epsList) / count($epsList) : 1;
        if ($mean == 0)
            $mean = 0.01;

        $cov = abs($stdDev / $mean);

        $predictability = 'low';
        if ($cov < 0.15)
            $predictability = 'high';
        elseif ($cov < 0.30)
            $predictability = 'medium';

        $tooHard = false;
        $reasons = [];

        if ($predictability === 'low') {
            // Check if it's acceptable for this strategy?
            // "Gate 3.5: min_earnings_predictability"
            if ($this->thresholds['min_earnings_predictability'] === 'medium') {
                // If strictly requiring medium+, this is a flag.
                $reasons[] = "Earnings predictability is Low (CoV: " . number_format($cov, 2) . ")";
                // $tooHard = true; // Maybe not instant kill, but warning.
            }
        }

        // We lack full 'Regulatory Risk' data without LLM or News parsing.
        // For MVP V3, we rely on predictability.

        return new GateResult(
            'gate_3_5',
            !$tooHard,
            ['earnings_cov' => $cov, 'predictability' => $predictability],
            !empty($reasons) ? implode('; ', $reasons) : null,
            ['too_hard' => $tooHard]
        );
    }
}
