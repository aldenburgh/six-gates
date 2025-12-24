<?php

namespace SixGates\Scoring;

use SixGates\Gates\GateResult;

class QualityTierClassifier
{
    public const TIER_EXCEPTIONAL = 'Exceptional';
    public const TIER_HIGH_QUALITY = 'High Quality';
    public const TIER_GOOD = 'Good';
    public const TIER_ACCEPTABLE = 'Acceptable'; // Previously 'Investable'
    public const TIER_UNINVESTABLE = 'Uninvestable';

    public function __construct(
        private array $thresholds // ['exceptional' => [...], ...]
    ) {
    }

    public function classify(AnalysisResult $result): string
    {
        if (!$result->passedQuality) {
            return self::TIER_UNINVESTABLE;
        }

        // Extract key metrics from Gate Results
        $gate2 = $this->getMetrics($result, 'gate_2');
        $gate15 = $this->getMetrics($result, 'gate_1_5');
        $gate275 = $this->getMetrics($result, 'gate_2_75');

        $spread = $gate2['spread'] ?? 0.0;
        $moat = $gate15['durability'] ?? 'none';
        $runway = $gate275['growth_category'] ?? 'none'; // 'long_runway', 'medium_runway'

        // Check Exceptional
        $cfg = $this->thresholds['exceptional'];
        if (
            $spread >= $cfg['min_roic_spread'] &&
            $this->checkMoat($moat, $cfg['min_moat_durability']) &&
            $this->checkRunway($runway, $cfg['min_runway'])
        ) {
            return self::TIER_EXCEPTIONAL;
        }

        // Check High Quality
        $cfg = $this->thresholds['high_quality'];
        if (
            $spread >= $cfg['min_roic_spread'] &&
            $this->checkMoat($moat, $cfg['min_moat_durability']) &&
            $this->checkRunway($runway, $cfg['min_runway'])
        ) {
            return self::TIER_HIGH_QUALITY;
        }

        // Check Good
        $cfg = $this->thresholds['good_quality'];
        if (
            $spread >= $cfg['min_roic_spread'] &&
            $this->checkMoat($moat, $cfg['min_moat_durability'])
        ) {
            return self::TIER_GOOD;
        }

        // Check Acceptable
        $cfg = $this->thresholds['acceptable'];
        if ($spread >= $cfg['min_roic_spread']) {
            return self::TIER_ACCEPTABLE;
        }

        return self::TIER_UNINVESTABLE; // Passed gates but failed spread? Should be caught by Gate 2 technically.
    }

    private function getMetrics(AnalysisResult $result, string $gateId): array
    {
        return $result->gateResults[$gateId]->metrics ?? [];
    }

    private function checkMoat(string $actual, string $required): bool
    {
        $levels = ['none' => 0, 'low' => 1, 'medium' => 2, 'high' => 3];
        return ($levels[$actual] ?? 0) >= ($levels[$required] ?? 0);
    }

    private function checkRunway(string $actual, string $required): bool
    {
        // 'long_runway' > 'medium_runway' > 'short_runway'
        $levels = ['none' => 0, 'low_growth' => 0, 'medium_runway' => 1, 'long_runway' => 2];
        return ($levels[$actual] ?? 0) >= ($levels[$required] ?? 0);
    }
}
