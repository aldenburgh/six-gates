<?php

namespace SixGates\DTOs;

use SixGates\Enums\TradeAction;
use SixGates\Enums\OrderType;
use SixGates\Enums\PortfolioType;
use SixGates\Enums\Urgency;

class RecommendationDTO
{
    public function __construct(
        public readonly TradeAction $action,
        public readonly PortfolioType $portfolioType,
        public readonly string $ticker,
        public readonly string $companyName,

        public readonly int $recommendedShares,
        public readonly float $currentPrice,
        public readonly ?float $estimatedCost,
        public readonly ?float $estimatedProceeds,

        public readonly OrderType $orderType,
        public readonly ?float $limitPrice,
        public readonly ?\DateTimeImmutable $limitValidUntil,
        public readonly string $orderTypeReason,

        public readonly string $narrativeSummary,
        public readonly string $fullNarrative,
        public readonly Urgency $urgency,

        // Optional context
        public readonly ?string $qualityTier = null,
        public readonly ?string $dividendTier = null,
        public readonly ?float $fairValue = null,
        public readonly ?float $discountPercent = null,
        public readonly ?float $incomeImpact = null
    ) {
    }
}
