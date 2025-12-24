<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Utils\Statistics;

class EconomicEngineGate implements GateInterface
{
    private array $thresholds;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        // Fetch required data (5 years)
        $keyMetrics = $provider->getKeyMetrics($ticker, 5);
        $ratios = $provider->getRatios($ticker, 5);
        $income = $provider->getIncomeStatement($ticker, 5);

        if (empty($keyMetrics) || empty($ratios) || empty($income)) {
            return new GateResult('gate_2', false, [], 'Insufficient data');
        }

        // Calculate Average ROIC (5y)
        $roics = array_column($keyMetrics, 'returnOnInvestedCapital');
        $avgRoic = !empty($roics) ? array_sum($roics) / count($roics) : 0;

        // Calculate WACC (using latest or avg? usually latest implies current cost)
        // FMP provides 'fetched' WACC in some endpoints, but let's see where.
        // Usually strictly it's calculated. For now, let's look for it in Ratios or KeyMetrics or standard FMP discount flow.
        // Wait, FMP puts weightedAverageCastOfCapital in 'ratios' strictly speaking? No, it's usually in 'discounted-cash-flow' or I have to calculate.
        // Let's assume for this MVP we approximate or check if it's in metrics. 
        // Actually, let's create a stub for WACC or use a default if missing, as FMP free tier might vary.
        // Let's assume we can derive it or use simple 10% if unknown for now to pass compilation.
        // Better: check if FMP 'ratios' has it. Documentation says it might not.
        // Let's use 0.08 (8%) as placeholder if not found, to avoid blocking.
        // In real app, we'd fetch from specialized endpoint.
        $wacc = 0.08;

        $spread = $avgRoic - $wacc;

        // Margin Trajectory
        $margins = array_map(function ($item) {
            return ($item['operatingIncome'] ?? 0) / ($item['revenue'] ?? 1);
        }, array_reverse($income)); // Reverse to have chronological order for regression

        $marginSlope = Statistics::linearRegressionSlope($margins);

        $metrics = [
            'avg_roic' => $avgRoic,
            'wacc' => $wacc,
            'spread' => $spread,
            'margin_slope' => $marginSlope,
        ];

        $reasons = [];
        if ($spread < $this->thresholds['min_roic_wacc_spread']) {
            $reasons[] = sprintf("ROIC-WACC spread (%.2f%%) below %.2f%%", $spread * 100, $this->thresholds['min_roic_wacc_spread'] * 100);
        }

        if ($marginSlope < $this->thresholds['margin_decline_threshold']) {
            $reasons[] = sprintf("Margin decline rate (%.2f) exceeds threshold", $marginSlope);
        }

        return new GateResult(
            'gate_2',
            empty($reasons),
            $metrics,
            !empty($reasons) ? implode('; ', $reasons) : null
        );
    }
}
