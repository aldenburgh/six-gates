<?php

namespace SixGates\Gates;

class GateResult
{
    public function __construct(
        public readonly string $gateName,
        public readonly bool $passed,
        public readonly array $metrics,
        public readonly ?string $killReason = null,
        public readonly array $details = []
    ) {
    }
}
