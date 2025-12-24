<?php

namespace SixGates\Domain;

class ShareCalculator
{
    /**
     * Calculate shares to buy based on recommended amount and current price.
     * Rounds DOWN to whole shares.
     */
    public function calculateBuyShares(float $amount, float $price): int
    {
        if ($price <= 0)
            return 0;
        return (int) floor($amount / $price);
    }

    /**
     * Calculate shares to sell based on quality tier and rules.
     * 
     * Growth Portfolio Rules:
     * - Exceptional: 25% only at 200%+ gain (handled by caller logic)
     * - High Quality: 50% at target
     * - Good/Acceptable: 100% at target
     */
    public function calculateSellShares(int $currentPositionShares, string $qualityTier, float $currentGainPercent = 0.0): int
    {
        $qualityTier = strtolower(str_replace(' ', '_', $qualityTier));

        switch ($qualityTier) {
            case 'exceptional':
                // Exceptional only sells if gain > 200%, and then only 25%
                if ($currentGainPercent >= 200.0) {
                    return (int) floor($currentPositionShares * 0.25);
                }
                return 0; // Hold forever otherwise

            case 'high_quality':
                return (int) floor($currentPositionShares * 0.50);

            case 'good_quality':
            case 'acceptable':
            default:
                return $currentPositionShares; // Sell 100%
        }
    }
}
