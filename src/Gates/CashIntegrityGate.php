<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

class CashIntegrityGate implements GateInterface
{
    private array $thresholds;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        $cashFlow = $provider->getCashFlow($ticker, 5);
        $income = $provider->getIncomeStatement($ticker, 5);
        $balance = $provider->getBalanceSheet($ticker, 5);

        if (empty($cashFlow) || empty($income) || empty($balance)) {
            return new GateResult('gate_3', false, [], 'Insufficient data');
        }

        // Use Latest Year for primary check, or Average? "FCF/Net Income > 80% consistently" implies average or all years.
        // Let's use average of 3 years to smooth out lumps.
        $years = 3;
        $totalFcf = 0;
        $totalNi = 0;

        for ($i = 0; $i < min($years, count($cashFlow)); $i++) {
            $totalFcf += $cashFlow[$i]['freeCashFlow'] ?? 0;
            $totalNi += $income[$i]['netIncome'] ?? 0;
        }

        $fcfConversion = $totalNi != 0 ? $totalFcf / $totalNi : 0;

        // Accruals Ratio: (Net Income - Operating Cash Flow) / Total Assets
        // Use latest year
        $latestNi = $income[0]['netIncome'] ?? 0;
        $latestOcf = $cashFlow[0]['operatingCashFlow'] ?? 0;
        $latestAssets = $balance[0]['totalAssets'] ?? 1;

        $accrualsRatio = ($latestNi - $latestOcf) / $latestAssets;

        // Receivables Divergence: AR Growth vs Revenue Growth
        // (AR_new - AR_old)/AR_old  vs (Rev_new - Rev_old)/Rev_old
        if (count($balance) >= 2 && count($income) >= 2) {
            $arGrowth = (($balance[0]['netReceivables'] ?? 0) - ($balance[1]['netReceivables'] ?? 0))
                / ($balance[1]['netReceivables'] ?: 1);

            $revGrowth = (($income[0]['revenue'] ?? 0) - ($income[1]['revenue'] ?? 0))
                / ($income[1]['revenue'] ?: 1);

            $receivablesDivergence = $arGrowth - $revGrowth;
        } else {
            $receivablesDivergence = 0;
        }

        $metrics = [
            'fcf_conversion' => $fcfConversion,
            'accruals_ratio' => $accrualsRatio,
            'receivables_divergence' => $receivablesDivergence
        ];

        $reasons = [];
        if ($fcfConversion < $this->thresholds['min_fcf_conversion']) {
            $reasons[] = sprintf("FCF Conversion (%.2f%%) below %.0f%%", $fcfConversion * 100, $this->thresholds['min_fcf_conversion'] * 100);
        }

        if ($accrualsRatio > $this->thresholds['max_accruals_ratio']) {
            $reasons[] = sprintf("Accruals ratio (%.2f) high", $accrualsRatio);
        }

        if ($receivablesDivergence > $this->thresholds['max_receivables_divergence']) {
            $reasons[] = sprintf("Receivables grew faster than revenue (%.2f%% divergence)", $receivablesDivergence * 100);
        }

        return new GateResult(
            'gate_3',
            empty($reasons),
            $metrics,
            !empty($reasons) ? implode('; ', $reasons) : null
        );
    }
}
