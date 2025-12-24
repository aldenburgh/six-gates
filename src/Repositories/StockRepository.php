<?php

namespace SixGates\Repositories;

use Doctrine\DBAL\Connection;

class StockRepository extends AbstractRepository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, 'stocks');
    }

    public function findByTicker(string $ticker): ?array
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('ticker = :ticker')
            ->setParameter('ticker', $ticker);

        $result = $query->executeQuery()->fetchAssociative();

        return $result === false ? null : $result;
    }

    public function upsert(array $data): void
    {
        $existing = $this->findByTicker($data['ticker']);

        if ($existing) {
            $this->connection->update($this->table, $data, ['ticker' => $data['ticker']]);
        } else {
            $this->connection->insert($this->table, $data);
        }
    }

    public function getActiveUniverse(): array
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('is_active = 1');

        return $query->executeQuery()->fetchAllAssociative();
    }
}
