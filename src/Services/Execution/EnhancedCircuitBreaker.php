<?php

namespace SixGates\Services\Execution;

use SixGates\Services\EarlyWarning\RiskAssessment;

class EnhancedCircuitBreaker
{
    public function __construct(
        private RiskAssessment $riskAssessment
    ) {
    }

    public function shouldHaltTrading(): bool
    {
        $assessment = $this->riskAssessment->assess();
        $score = $assessment['systemic_risk_score'] ?? 0;
        $vix = $assessment['macro_metrics']['vix'] ?? 0;

        // Halt if Systemic Risk is Critical (> 80)
        if ($score > 80) {
            return true;
        }

        // Halt if VIX itself is extreme (> 40)
        if ($vix > 40) {
            return true;
        }

        return false;
    }

    public function detectOpportunities(): bool
    {
        $assessment = $this->riskAssessment->assess();
        $score = $assessment['systemic_risk_score'] ?? 0;

        // Panic Selling Opportunity: Risk is Medium-High (40-60)
        // implying fear is present but not system failure.
        // (Simplified logic).
        if ($score >= 40 && $score <= 60) {
            return true;
        }

        return false;
    }

    public function getStatus(): array
    {
        return [
            'halted' => $this->shouldHaltTrading(),
            'opportunity_zone' => $this->detectOpportunities(),
            'risk_score' => $this->riskAssessment->assess()['systemic_risk_score'] ?? 0
        ];
    }
}
