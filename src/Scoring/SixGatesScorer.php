<?php

namespace SixGates\Scoring;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Gates\GateInterface;
use SixGates\Gates\GateResult;

class SixGatesScorer
{
    /** @var GateInterface[] */
    private array $gates = [];

    public function addGate(GateInterface $gate): void
    {
        $this->gates[] = $gate;
    }

    public function score(string $ticker, DataProviderInterface $provider): AnalysisResult
    {
        $results = [];
        $allPassed = true;

        foreach ($this->gates as $gate) {
            $result = $gate->analyze($ticker, $provider);
            $results[$result->gateName] = $result;

            // Check if this is a Kill Gate (Gates 1, 2, 2.5, 3 usually)
            // Strategy: Gate 4, 5 are resizing/valuation, not hard kill?
            // "Gate 4: Fail = wait for better price, not kill"
            // "Gate 5: Determines position sizing, not kill"
            // So only specific gates kill the "Quality" check.

            $isQualityGate = in_array($result->gateName, ['gate_1', 'gate_2', 'gate_2_5', 'gate_3', 'gate_3_5']);
            if ($isQualityGate && !$result->passed) {
                $allPassed = false;
            }
        }

        return new AnalysisResult($ticker, $results, $allPassed);
    }
}
