<?php

namespace SixGates\Domain;

use SixGates\Enums\OrderType;
use SixGates\Enums\Urgency;

class OrderTypeAdvisor
{
    public function advise(Urgency $urgency): OrderType
    {
        // 4.1 & 4.2: Urgent/Critical -> MARKET
        if ($urgency === Urgency::CRITICAL || $urgency === Urgency::HIGH) {
            return OrderType::MARKET;
        }

        // 4.3: Standard -> LIMIT
        return OrderType::LIMIT;
    }

    /**
     * Calculate Limit Price based on Section 4.4
     */
    public function calculateLimitPrice(string $action, float $currentPrice, float $fairValue): float
    {
        if ($action === 'sell') {
            // For Sell: At or slightly above current
            // Logic: If current > fair value, sell at current. 
            // Simplified: Document says "At or slightly above current".
            return $currentPrice;
        }

        // For Buy: Based on discount (Table 4.4)
        if ($fairValue <= 0)
            return $currentPrice;

        $discountFn = fn($p, $fv) => ($fv - $p) / $fv;
        $discount = $discountFn($currentPrice, $fairValue);

        if ($discount > 0.20) {
            // > 20% discount -> Use current price (Start position immediately)
            return $currentPrice;
        } elseif ($discount >= 0.10) {
            // 10-20% discount -> Current - 2%
            return $currentPrice * 0.98;
        } else {
            // < 10% discount -> Current - 5% (Be patient)
            return $currentPrice * 0.95;
        }
    }

    /**
     * Calculate Validy Period based on Section 4.5
     */
    public function calculateValidity(\DateTimeImmutable $now, float $vix = 20.0): \DateTimeImmutable
    {
        // 4.5: 
        // VIX > 25: 3 days
        // Normal: 7 days
        // Late cycle: 14 days (Ignored for now, assuming normal/vix logic)

        $days = 7;
        if ($vix > 25.0) {
            $days = 3;
        }

        return $now->modify("+{$days} days");
    }
}
