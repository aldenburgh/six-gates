<?php

namespace SixGates\Entities;

use SixGates\Enums\PortfolioType;

class Position
{
    public function __construct(
        public readonly string $id,
        public readonly string $ticker,
        public readonly string $companyName,
        public readonly PortfolioType $portfolioType,

        public float $shares,
        public float $averageCost,
        public float $costBasis,

        // Status
        public string $status = 'open', // 'open' or 'closed'
        public ?\DateTimeImmutable $openedAt = null,
        public ?\DateTimeImmutable $closedAt = null,

        // Metadata (Optional for instantiation, but typically present)
        public ?string $qualityTier = null,
        public ?string $dividendTier = null,

        // Current Market Data (Mutable)
        public ?float $currentPrice = null,
        public ?float $marketValue = null,
        public ?float $gainLoss = null,
        public ?float $gainLossPercent = null
    ) {
        if ($this->openedAt === null) {
            $this->openedAt = new \DateTimeImmutable();
        }
    }
}
