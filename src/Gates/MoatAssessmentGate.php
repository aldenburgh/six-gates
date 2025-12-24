<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Moat\LLMMoatAssessor;
use SixGates\Moat\MoatAssessment;

class MoatAssessmentGate implements GateInterface
{
    public function __construct(
        private array $thresholds,
        private ?LLMMoatAssessor $assessor = null
    ) {
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        // For CLI or automated runs without LLM setup, we might skip or use basic defaults
        if (!$this->assessor) {
            return new GateResult('gate_1_5', true, [], 'Moat Assessor not configured');
        }

        $assessment = $this->assessor->assess($ticker);

        // Pass/Fail Logic
        // In V3, we don't necessarily "Kill" on no moat, but it affects sizing/tier.
        // However, if we WANT to filter for quality, we might require at least 'low' durability?
        // Let's assume it passes unless explicitly configured to filter strictly.
        // The prompt says "Gate 1.5... Purpose: Identify moat type... for holding rule determination".
        // It doesn't explicitly state "Kill Criteria" like others, effectively it's an informational gate that feeds classification.

        $metrics = $assessment->toArray();

        $status = 'PASSED';
        // Example check: If user config demands a moat (e.g. for a "quality" screen)
        // if ($this->thresholds['require_moat_for_exceptional'] && $assessment->durability === 'none') { ... } 
        // But for general screening, we just report.

        return new GateResult(
            'gate_1_5',
            true, // Always passes, just categorizes
            $metrics,
            null,
            ['moat_durability' => $assessment->durability] // Context for Scorer
        );
    }
}
