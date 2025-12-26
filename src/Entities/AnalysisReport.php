<?php

namespace SixGates\Entities;

class AnalysisReport
{
    public function __construct(
        public ?int $id,
        public string $ticker,
        public string $analysisDate,
        public string $reportContent,
        public ?string $createdAt = null
    ) {
    }
}
