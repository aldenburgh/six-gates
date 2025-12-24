<?php

namespace SixGates\Entities;

use SixGates\Enums\TradeAction;
use SixGates\Enums\PortfolioType;

class ExecutionLog
{
    public function __construct(
        public readonly string $id,
        public readonly string $recommendationId,
        public readonly string $ticker,
        public readonly TradeAction $action,
        public readonly PortfolioType $portfolioType,

        public readonly int $actualShares,
        public readonly float $actualPrice,
        public readonly float $commission,
        public readonly \DateTimeImmutable $executionDate,

        public readonly ?string $broker = null,
        public readonly ?string $notes = null,

        // Snapshot of Recommendation
        public int $recommendedShares = 0,
        public float $recommendedPrice = 0.0,
        public string $recommendedOrderType = 'market',
        public float $recommendedTotal = 0.0,

        public float $actualTotal = 0.0,

        // Calculated Variances
        public ?int $sharesVariance = null,
        public ?float $sharesVariancePercent = null,
        public ?float $priceVariance = null,
        public ?float $priceVariancePercent = null,
        public ?float $totalVariance = null,
        public ?float $totalVariancePercent = null,

        public ?\DateTimeImmutable $createdAt = null
    ) {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }
}
