<?php

namespace SixGates\Moat;

class MoatAssessment
{
    public function __construct(
        public readonly ?string $moatType,           // Primary moat type
        public readonly array $secondaryMoats,       // Additional moats
        public readonly string $durability,          // 'high', 'medium', 'low', 'none'
        public readonly array $moatEvidence,         // Supporting evidence
        public readonly array $moatThreats,          // What could erode the moat
        public readonly string $assessmentMethod,    // 'llm', 'human', 'hybrid'
        public readonly ?string $humanOverride,      // Human can override LLM
        public readonly float $confidenceScore       // 0-1 confidence in assessment
    ) {
    }

    public function toArray(): array
    {
        return [
            'moat_type' => $this->moatType,
            'secondary_moats' => $this->secondaryMoats,
            'durability' => $this->durability,
            'evidence' => $this->moatEvidence,
            'threats' => $this->moatThreats,
            'method' => $this->assessmentMethod,
            'confidence' => $this->confidenceScore
        ];
    }
}
