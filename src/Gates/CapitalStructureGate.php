<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

class CapitalStructureGate implements GateInterface
{
    private array $thresholds;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        $metrics = $provider->getKeyMetrics($ticker, 5);
        $income = $provider->getIncomeStatement($ticker, 5);
        $balance = $provider->getBalanceSheet($ticker, 5);

        if (empty($metrics) || empty($income) || empty($balance)) {
            return new GateResult('gate_2_5', false, [], 'Insufficient data');
        }

        // Net Debt / EBITDA (use latest)
        $netDebtEbitda = $metrics[0]['netDebtToEBITDA'] ?? 0;

        // Interest Coverage (EBIT / Interest Expense) (use latest)
        $ebit = $income[0]['operatingIncome'] ?? 0; // Or earningsBeforeInterestTaxes
        $interestExpense = $income[0]['interestExpense'] ?? 1; // Avoid div by zero
        $interestCoverage = $interestExpense != 0 ? $ebit / $interestExpense : 100;

        // Debt Maturity Analysis: Flag if >30% due within 24 months
        // FMP Balance Sheet usually has shortTermDebt and longTermDebt.
        // It doesn't give granular maturity schedule in standard BS.
        // We can approximate: Short Term Debt / Total Debt.
        $stDebt = $balance[0]['shortTermDebt'] ?? 0;
        $ltDebt = $balance[0]['longTermDebt'] ?? 0;
        $totalDebt = $stDebt + $ltDebt;

        $debtDue24mPct = $totalDebt > 0 ? $stDebt / $totalDebt : 0;
        // Note: Short Term Debt is due within 12 months. 24m is harder to guess without footnotes.
        // We will use ST Debt ratio as proxy for near-term stress.

        // Stress Test: Can they service debt if revenue drops 30%?
        // Assume EBITDA drops similarly (or operating leverage makes it worse).
        // Let's assume EBITDA drops by 30% * Operating Leverage.
        // Simplified: EBITDA drops 30% (linear).
        // Check if Stress EBITDA > Interest Expense.
        $currentEbitda = $income[0]['ebitda'] ?? 0;
        $stressEbitda = $currentEbitda * (1 - $this->thresholds['stress_test_revenue_drop']);
        $stressTestPassed = $stressEbitda > $interestExpense;

        $gateMetrics = [
            'net_debt_ebitda' => $netDebtEbitda,
            'interest_coverage' => $interestCoverage,
            'debt_due_12m_pct' => $debtDue24mPct,
            'stress_ebitda' => $stressEbitda,
            'interest_expense' => $interestExpense
        ];

        $reasons = [];
        if ($netDebtEbitda > $this->thresholds['max_net_debt_ebitda']) {
            $reasons[] = sprintf("Net Debt/EBITDA (%.2fx) exceeds limit %.1fx", $netDebtEbitda, $this->thresholds['max_net_debt_ebitda']);
        }

        if ($interestCoverage < $this->thresholds['min_interest_coverage']) {
            $reasons[] = sprintf("Interest Coverage (%.2fx) below min %.1fx", $interestCoverage, $this->thresholds['min_interest_coverage']);
        }

        if ($debtDue24mPct > $this->thresholds['max_debt_due_24m_pct']) {
            $reasons[] = sprintf("Short Term Debt (%.0f%%) exceeds safe limit", $debtDue24mPct * 100);
        }

        if (!$stressTestPassed) {
            $reasons[] = "Failed revenue stress test";
        }

        return new GateResult(
            'gate_2_5',
            empty($reasons),
            $gateMetrics,
            !empty($reasons) ? implode('; ', $reasons) : null
        );
    }
}
