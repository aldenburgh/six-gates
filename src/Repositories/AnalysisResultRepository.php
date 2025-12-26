<?php

namespace SixGates\Repositories;

use Doctrine\DBAL\Connection;
use SixGates\Scoring\AnalysisResult;

class AnalysisResultRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, 'stock_analyses');
    }

    public function save(AnalysisResult $result): void
    {
        // 1. Prepare Data for `stock_analyses` schema (V6 Architecture)
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $data = [
            'id' => $result->id ?? $uuid,
            'ticker' => $result->ticker,
            'analysis_date' => date('Y-m-d'),

            // Tier
            'quality_tier' => $this->mapQualityTier($result->qualityTier),

            // Gate 1
            'gate_1_passed' => $this->getGatePassed($result, 'gate_1') ? 1 : 0,
            'gate_1_data' => $this->getGateDataJson($result, 'gate_1'),

            // Gate 1.5
            'gate_1_5_moat_type' => $result->gateResults['gate_1_5']->metrics['moat_type'] ?? null,
            'gate_1_5_durability' => $result->gateResults['gate_1_5']->metrics['moat_durability'] ?? null, // Note: Durability is in Details actually? Check logs. Log says "Durability: high".
            'gate_1_5_data' => $this->getGateDataJson($result, 'gate_1_5'),

            // Gate 2
            'gate_2_passed' => $this->getGatePassed($result, 'gate_2') ? 1 : 0,
            'gate_2_roic' => $result->gateResults['gate_2']->metrics['avg_roic'] ?? null,
            'gate_2_wacc' => $result->gateResults['gate_2']->metrics['wacc'] ?? null,
            'gate_2_data' => $this->getGateDataJson($result, 'gate_2'),

            // Gate 2.5
            'gate_2_5_passed' => $this->getGatePassed($result, 'gate_2_5') ? 1 : 0,
            'gate_2_5_debt_ebitda' => $result->gateResults['gate_2_5']->metrics['net_debt_ebitda'] ?? null,
            'gate_2_5_data' => $this->getGateDataJson($result, 'gate_2_5'),

            // Gate 2.75
            'gate_2_75_category' => $result->gateResults['gate_2_75']->details['runway_category'] ?? null,
            'gate_2_75_data' => $this->getGateDataJson($result, 'gate_2_75'),

            // Gate 3
            'gate_3_passed' => $this->getGatePassed($result, 'gate_3') ? 1 : 0,
            'gate_3_fcf_conversion' => $result->gateResults['gate_3']->metrics['fcf_conversion'] ?? null,
            'gate_3_data' => $this->getGateDataJson($result, 'gate_3'),

            // Gate 3.5
            'gate_3_5_too_hard' => $this->getGatePassed($result, 'gate_3_5') ? 0 : 1, // Pass means NOT too hard. Schema says `gate_3_5_too_hard`. Logic inversion? Schema usually stores flags. Let's assume passed=1 means OK. Wait, schema field is `too_hard`. If passed=true (Not too hard), then too_hard=0.
            'gate_3_5_data' => $this->getGateDataJson($result, 'gate_3_5'),

            // Gate 4
            'gate_4_in_zone' => $this->getGatePassed($result, 'gate_4') ? 1 : 0,
            'gate_4_fair_value' => $result->gateResults['gate_4']->metrics['fair_value'] ?? null, // Need to ensure Calculator outputs 'fair_value' in metric? Often it's just PEG/PE.
            'gate_4_data' => $this->getGateDataJson($result, 'gate_4'),

            // Gate 5
            'gate_5_data' => $this->getGateDataJson($result, 'gate_5'),

            // Context
            // Schema doesn't have score column? It has `gate_5_score`.
            // 'gate_5_score' => ...

            // Dividend (Optional, if Dividend Portfolio)
        ];

        // Handle UPSERT logic manually or delete-insert for same day?
        // Schema: UNIQUE INDEX `idx_analysis_ticker_date` (`ticker`, `analysis_date`)
        // So we should delete previous for today or use upsert.
        // DBAL insert() throws on duplicate.
        // Let's delete for today first.
        $this->connection->delete($this->table, ['ticker' => $result->ticker, 'analysis_date' => date('Y-m-d')]);

        $this->connection->insert($this->table, $data);
    }

    private function mapQualityTier(?string $tier): ?string
    {
        if ($tier === 'Uninvestable' || empty($tier)) {
            return null;
        }
        // DB Enum: 'exceptional', 'high_quality', 'good_quality', 'acceptable'
        // Classes constants: 'Exceptional', 'High Quality', 'Good', 'Acceptable'
        // Need mapping? 
        // Let's assume strict mapping or sanitize.
        // Convert to snake_case lower?
        // 'High Quality' -> 'high_quality'
        // 'Good' -> 'good_quality' (Wait, enum is 'good_quality', class is 'Good'?)
        // Let's verify definitions.

        $map = [
            'Exceptional' => 'exceptional',
            'High Quality' => 'high_quality',
            'Good' => 'good_quality', // Mapping 'Good' to 'good_quality'
            'Acceptable' => 'acceptable',
        ];

        return $map[$tier] ?? null;
    }

    private function getPassedGateIds(AnalysisResult $result): array
    {
        $passed = [];
        foreach ($result->gateResults as $gr) {
            if ($gr->passed)
                $passed[] = $gr->gateName;
        }
        return $passed;
    }

    private function getGateDataJson(AnalysisResult $result, string $gateId): ?string
    {
        // Direct access efficiency
        if (isset($result->gateResults[$gateId])) {
            $gr = $result->gateResults[$gateId];
            $data = array_merge($gr->metrics, $gr->details);
            return json_encode($data);
        }
        return null;
    }

    private function getGatePassed(AnalysisResult $result, string $gateId): bool
    {
        return $result->gateResults[$gateId]->passed ?? false;
    }
}
