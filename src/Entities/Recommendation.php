<?php

namespace SixGates\Entities;

use SixGates\Enums\TradeAction;
use SixGates\Enums\OrderType;
use SixGates\Enums\PortfolioType;
use SixGates\Enums\RecommendationStatus;
use SixGates\Enums\Urgency;

class Recommendation
{
    public function __construct(
        public readonly string $id,
        public readonly \DateTimeImmutable $createdAt,
        public readonly \DateTimeImmutable $expiresAt,

        public readonly TradeAction $action,
        public readonly PortfolioType $portfolioType,
        public readonly string $ticker,
        public readonly string $companyName,

        public int $recommendedShares,
        public float $currentPrice, // Price at time of recommendation
        public ?float $estimatedCost,
        public ?float $estimatedProceeds,

        public OrderType $orderType,
        public ?float $limitPrice,
        public ?\DateTimeImmutable $limitValidUntil,
        public string $orderTypeReason,

        public string $narrativeSummary,
        public string $fullNarrative,
        public Urgency $urgency,

        public RecommendationStatus $status = RecommendationStatus::PENDING,

        // Optional context
        public ?string $qualityTier = null,
        public ?float $incomeImpact = null, // Annual
        public ?float $goalImpactPercent = null,

        public ?\DateTimeImmutable $approvedAt = null,
        public ?\DateTimeImmutable $deniedAt = null,
        public ?string $denialReason = null,
        public ?\DateTimeImmutable $executedAt = null
    ) {
    }
}
