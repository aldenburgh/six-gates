<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

class ReinvestmentRunwayGate implements GateInterface
{
    public function __construct(private array $thresholds)
    {
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        // Need multi-year data to calc incremental returns
        $years = 4; // need 4 years to get 3 delta periods?
        $financials = $provider->getKeyMetrics($ticker, $years);
        $income = $provider->getIncomeStatement($ticker, $years);
        $cashflow = $provider->getCashFlow($ticker, $years);

        if (empty($financials) || empty($income) || empty($cashflow)) {
            return new GateResult('gate_2_75', false, [], 'Insufficient data');
        }

        // 1. Calculate Latest Reinvestment Rate
        // RR = (CapEx - Dep + ChangeWC) / NOPAT
        // NOPAT = EBIT * (1 - TaxRate)
        $latestCf = $cashflow[0];
        $latestInc = $income[0];

        $capex = abs($latestCf['capitalExpenditure'] ?? 0);
        $dep = $latestCf['depreciationAndAmortization'] ?? 0;
        $wcChange = $latestCf['changeInWorkingCapital'] ?? 0;

        // NOPAT approx
        $ebit = $latestInc['operatingIncome'] ?? 1;
        $taxExp = $latestInc['incomeTaxExpense'] ?? 0;
        $preTax = $latestInc['incomeBeforeTax'] ?? 1;
        $taxRate = $preTax != 0 ? $taxExp / $preTax : 0.21;
        $nopat = $ebit * (1 - $taxRate);

        $reinvestment = $capex - $dep + $wcChange;
        // If NOPAT is negative/zero, RR is meaningless.
        $reinvestmentRate = ($nopat > 0) ? $reinvestment / $nopat : 0;

        // 2. Incremental ROIC (ROIIC)
        // ROIIC = (Change in NOPAT) / (Change in Invested Capital) over last X years
        // Let's take [0] vs [3] (3 year span)
        $startNopat = $this->calculateNopat($income[count($income) - 1]);
        $endNopat = $this->calculateNopat($income[0]);

        // Invested Capital = Total Assets - Non-Interest Bearing Current Liabs?
        // Or strictly 'investedCapital' from KeyMetrics
        $startIC = $financials[count($financials) - 1]['investedCapital'] ?? 1;
        $endIC = $financials[0]['investedCapital'] ?? 1;

        $deltaNopat = $endNopat - $startNopat;
        $deltaIC = $endIC - $startIC;

        $incrementalRoic = ($deltaIC != 0) ? $deltaNopat / $deltaIC : 0;

        // 3. Categorize
        // "Compounder" if High RR + High IncROIC
        $category = 'mature';
        if ($reinvestmentRate > 0.5 && $incrementalRoic > 0.15)
            $category = 'long_runway';
        elseif ($reinvestmentRate > 0.3 && $incrementalRoic > 0.10)
            $category = 'medium_runway';
        elseif ($reinvestmentRate > 0.1)
            $category = 'limited_runway';

        $metrics = [
            'reinvestment_rate' => $reinvestmentRate,
            'incremental_roic' => $incrementalRoic,
            'growth_category' => $category,
            'nopat_growth_3y' => ($startNopat > 0) ? ($endNopat - $startNopat) / $startNopat : 0
        ];

        return new GateResult(
            'gate_2_75',
            true, // Analysis gate, doesn't kill
            $metrics,
            null,
            ['runway_category' => $category]
        );
    }

    private function calculateNopat(array $incomeItem): float
    {
        $ebit = $incomeItem['operatingIncome'] ?? 0;
        $tax = $incomeItem['incomeTaxExpense'] ?? 0;
        $preTax = $incomeItem['incomeBeforeTax'] ?? 1;
        $rate = ($preTax != 0) ? $tax / $preTax : 0.21;
        return $ebit * (1 - $rate);
    }
}
