<?php

namespace SixGates\Repositories;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class AbstractRepository
{
    public function __construct(
        protected Connection $connection,
        protected string $table
    ) {
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }

    public function find(mixed $id): ?array
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from($this->table)
            ->where('id = :id')
            ->setParameter('id', $id);

        $result = $query->executeQuery()->fetchAssociative();

        return $result === false ? null : $result;
    }

    public function findAll(): array
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from($this->table);

        return $query->executeQuery()->fetchAllAssociative();
    }
}
