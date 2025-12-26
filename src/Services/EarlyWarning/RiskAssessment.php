<?php

namespace SixGates\Services\EarlyWarning;

class RiskAssessment
{
    public function __construct(
        private MacroMonitor $macroMonitor
    ) {
    }

    public function assess(): array
    {
        $vix = $this->macroMonitor->getVixLevel();
        $yieldSpread = $this->macroMonitor->getYieldSpread();
        $inflation = $this->macroMonitor->getInflationRate();

        $score = $this->calculateScore($vix, $yieldSpread, $inflation);

        return [
            'macro_metrics' => [
                'vix' => $vix,
                'yield_spread_10y_2y' => $yieldSpread,
                'inflation' => $inflation
            ],
            'systemic_risk_score' => $score,
            'risk_level' => $this->classifyRisk($score),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function calculateScore(float $vix, float $spread, float $inflation): int
    {
        $score = 0;

        // VIX Component (0-40 pts)
        if ($vix > 30)
            $score += 40;
        elseif ($vix > 20)
            $score += 20;
        elseif ($vix > 15)
            $score += 10;

        // Yield Curve Component (0-40 pts)
        if ($spread < 0)
            $score += 40; // Inverted curve = High Risk
        elseif ($spread < 0.2)
            $score += 20;

        // Inflation Component (0-20 pts)
        if ($inflation > 5.0)
            $score += 20;
        elseif ($inflation > 3.0)
            $score += 10;

        return min($score, 100);
    }

    private function classifyRisk(int $score): string
    {
        if ($score >= 60)
            return 'HIGH';
        if ($score >= 30)
            return 'MEDIUM';
        return 'LOW';
    }
}
