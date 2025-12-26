<?php

namespace SixGates\Repositories;

use Doctrine\DBAL\Connection;
use SixGates\Entities\AnalysisReport;
use PDO;

class ReportRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, 'analysis_reports');
    }

    public function save(AnalysisReport $report): void
    {
        $data = [
            'ticker' => $report->ticker,
            'analysis_date' => $report->analysisDate,
            'report_content' => $report->reportContent,
        ];

        // Delete existing report for same date/ticker to allow re-runs
        $this->connection->delete($this->table, [
            'ticker' => $report->ticker,
            'analysis_date' => $report->analysisDate
        ]);

        $this->connection->insert($this->table, $data);
    }

    public function findLatestByTicker(string $ticker): ?AnalysisReport
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*')
            ->from($this->table)
            ->where('ticker = :ticker')
            ->orderBy('analysis_date', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('ticker', $ticker);

        $result = $queryBuilder->executeQuery()->fetchAssociative();

        if (!$result) {
            return null;
        }

        return new AnalysisReport(
            id: (int) $result['id'],
            ticker: $result['ticker'],
            analysisDate: $result['analysis_date'],
            reportContent: $result['report_content'],
            createdAt: $result['created_at']
        );
    }
}
