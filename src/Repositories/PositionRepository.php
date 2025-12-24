<?php

namespace SixGates\Repositories;

use \PDO;
use SixGates\Entities\Position;
use SixGates\Enums\PortfolioType;

class PositionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByTicker(string $ticker, PortfolioType $type): ?Position
    {
        $stmt = $this->db->prepare("
            SELECT * FROM positions 
            WHERE ticker = :ticker 
            AND portfolio_type = :type 
            AND status = 'open' 
            LIMIT 1
        ");
        $stmt->execute([
            'ticker' => $ticker,
            'type' => $type->value
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function getAllOpen(): array
    {
        $stmt = $this->db->query("SELECT * FROM positions WHERE status = 'open' ORDER BY ticker ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function save(Position $position): void
    {
        // Check if exists
        $exists = $this->findById($position->id);

        if ($exists) {
            $this->update($position);
        } else {
            $this->insert($position);
        }
    }

    public function findById(string $id): ?Position
    {
        $stmt = $this->db->prepare("SELECT * FROM positions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRowToEntity($row) : null;
    }

    private function insert(Position $p): void
    {
        $sql = "INSERT INTO positions (
            id, ticker, company_name, portfolio_type, 
            shares, average_cost, cost_basis, 
            status, opened_at, quality_tier, dividend_tier
        ) VALUES (
            :id, :ticker, :company_name, :portfolio_type,
            :shares, :average_cost, :cost_basis,
            :status, :opened_at, :quality_tier, :dividend_tier
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $p->id,
            'ticker' => $p->ticker,
            'company_name' => $p->companyName,
            'portfolio_type' => $p->portfolioType->value,
            'shares' => $p->shares,
            'average_cost' => $p->averageCost,
            'cost_basis' => $p->costBasis,
            'status' => $p->status,
            'opened_at' => $p->openedAt->format('Y-m-d H:i:s'),
            'quality_tier' => $p->qualityTier,
            'dividend_tier' => $p->dividendTier
        ]);
    }

    private function update(Position $p): void
    {
        $sql = "UPDATE positions SET 
            shares = :shares,
            average_cost = :average_cost,
            cost_basis = :cost_basis,
            status = :status,
            closed_at = :closed_at,
            current_price = :current_price,
            market_value = :market_value,
            gain_loss = :gain_loss,
            gain_loss_percent = :gain_loss_percent
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'shares' => $p->shares,
            'average_cost' => $p->averageCost,
            'cost_basis' => $p->costBasis,
            'status' => $p->status,
            'closed_at' => $p->closedAt?->format('Y-m-d H:i:s'),
            'current_price' => $p->currentPrice,
            'market_value' => $p->marketValue,
            'gain_loss' => $p->gainLoss,
            'gain_loss_percent' => $p->gainLossPercent,
            'id' => $p->id
        ]);
    }

    private function mapRowToEntity(array $row): Position
    {
        $p = new Position(
            id: $row['id'],
            ticker: $row['ticker'],
            companyName: $row['company_name'],
            portfolioType: PortfolioType::from($row['portfolio_type']),
            shares: (float) $row['shares'],
            averageCost: (float) $row['average_cost'],
            costBasis: (float) $row['cost_basis'],
            status: $row['status'],
            openedAt: new \DateTimeImmutable($row['opened_at']),
            closedAt: $row['closed_at'] ? new \DateTimeImmutable($row['closed_at']) : null,
            qualityTier: $row['quality_tier'],
            dividendTier: $row['dividend_tier'],
            currentPrice: $row['current_price'] ? (float) $row['current_price'] : null,
            marketValue: $row['market_value'] ? (float) $row['market_value'] : null,
            gainLoss: $row['gain_loss'] ? (float) $row['gain_loss'] : null,
            gainLossPercent: $row['gain_loss_percent'] ? (float) $row['gain_loss_percent'] : null
        );
        return $p;
    }
}
