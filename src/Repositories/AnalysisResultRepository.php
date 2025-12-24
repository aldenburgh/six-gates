<?php

namespace SixGates\Repositories;

use Doctrine\DBAL\Connection;
use SixGates\Scoring\AnalysisResult;

class AnalysisResultRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, 'analysis_results');
    }

    public function save(AnalysisResult $result): void
    {
        // 1. Ensure Stock Exists
        // In a real app we'd use StockRepository separately, but here we might need quick check.
        // Assuming stock exists or we might fail FK constraint.
        // Let's insert ignore/upsert stock stub if needed?
        // For strictness, we assume stock exists. If not, analyze.php should ensure it.

        // 2. Prepare Data
        $data = [
            'ticker' => $result->ticker,
            'run_date' => date('Y-m-d'),
            'gate_results' => json_encode($result->gateResults),
            'passed_gates' => json_encode($this->getPassedGateIds($result)),
            'conviction_score' => $result->getConvictionScore(),
            'is_latest' => 1,

            // New V3 Columns
            'gate_1_5_data' => $this->getGateDataJson($result, 'gate_1_5'),
            'gate_2_75_data' => $this->getGateDataJson($result, 'gate_2_75'),
            'gate_3_5_data' => $this->getGateDataJson($result, 'gate_3_5'),
            'gate_3_5_passed' => $this->getGatePassed($result, 'gate_3_5') ? 1 : 0,

            'quality_tier' => $this->mapQualityTier($result->qualityTier)
            // Removed position_size and market_context as they are not in the table schema
        ];

        // Unset previous latest
        $this->connection->update($this->table, ['is_latest' => 0], ['ticker' => $result->ticker]);

        // Insert
        // Use try-catch for insert to report specific errors if needed
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
