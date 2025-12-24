<?php

namespace SixGates\MarketContext;

class MarketContext
{
    public const PHASE_ACCUMULATION = 'accumulation';
    public const PHASE_BULL = 'bull';
    public const PHASE_DISTRIBUTION = 'distribution';
    public const PHASE_BEAR = 'bear';
    public const PHASE_CRASH = 'crash';

    public function __construct(
        public readonly string $phase,
        public readonly float $riskScore, // 0-100 (High = Crash Risk)
        public readonly float $discountRateAdjustment, // e.g. +0.02 for high risk
        public readonly array $metrics // ['ma_200_spy' => ..., 'current_spy' => ...]
    ) {
    }
}
