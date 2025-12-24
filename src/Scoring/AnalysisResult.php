<?php

namespace SixGates\Scoring;

class AnalysisResult
{
    public function __construct(
        public readonly string $ticker,
        public readonly array $gateResults,
        public readonly bool $passedQuality,
        public readonly ?string $qualityTier = null,
        public readonly ?float $positionSize = null,
        public readonly ?array $marketContext = null
    ) {
    }

    public function withTierAndSize(string $tier, float $size, array $context = []): self
    {
        return new self(
            $this->ticker,
            $this->gateResults,
            $this->passedQuality,
            $tier,
            $size,
            $context
        );
    }

    public function getConvictionScore(): float
    {
        // Simple scoring based on how many gates passed or specific metrics
        return 0.0;
    }
}
