<?php

namespace SixGates\Scoring;

use SixGates\MarketContext\MarketContext;

class PositionSizer
{
    public function __construct(
        private array $config // thresholds['position_sizing']
    ) {
    }

    public function calculate(string $tier, ?MarketContext $context): float
    {
        // Map Tier to Size Key
        $sizeKey = match ($tier) {
            QualityTierClassifier::TIER_EXCEPTIONAL => 'maximum', // 15%
            QualityTierClassifier::TIER_HIGH_QUALITY => 'large', // 10%
            QualityTierClassifier::TIER_GOOD => 'standard', // 5%
            QualityTierClassifier::TIER_ACCEPTABLE => 'small', // 3%
            default => 'starter', // 1% or 0
        };

        if ($tier === QualityTierClassifier::TIER_UNINVESTABLE) {
            return 0.0;
        }

        $baseSize = $this->config[$sizeKey] ?? 0.03;

        // Adjust for Market Context
        if ($context) {
            // If Bear Market, reduce exposure
            if ($context->phase === MarketContext::PHASE_BEAR) {
                $baseSize *= 0.5; // Cut size in half
            }
            // If high risk score > 70, reduce
            if ($context->riskScore > 70) {
                $baseSize *= 0.7;
            }
            // If Crash Phase, size = 0? Or Opportunity?
            // "Opportunity Detection" logic might override this, but standard sizing is cautious.
            if ($context->phase === MarketContext::PHASE_CRASH) {
                // Aggressive buying opportunity if stock is Exceptional?
                // For safety, let's keep it cautious in automated logic unless specifically overridden.
            }
        }

        return $baseSize;
    }
}
