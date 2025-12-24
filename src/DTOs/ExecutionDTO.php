<?php

namespace SixGates\DTOs;

use SixGates\Enums\TradeAction;
use SixGates\Enums\PortfolioType;

class ExecutionDTO
{
    public function __construct(
        public readonly string $recommendationId,
        public readonly string $ticker,
        public readonly TradeAction $action,
        public readonly PortfolioType $portfolioType,

        public readonly int $sharesExecuted,
        public readonly float $pricePerShare,
        public readonly float $commission,
        public readonly \DateTimeImmutable $executionDate,

        public readonly ?string $broker = null,
        public readonly ?string $notes = null // Fix: Added semicolon here
    ) {
    }
}
