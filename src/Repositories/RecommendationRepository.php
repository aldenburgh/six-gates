<?php

namespace SixGates\Repositories;

use \PDO;
use SixGates\Entities\Recommendation;
use SixGates\Enums\TradeAction;
use SixGates\Enums\OrderType;
use SixGates\Enums\PortfolioType;
use SixGates\Enums\RecommendationStatus;
use SixGates\Enums\Urgency;

class RecommendationRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(Recommendation $rec): void
    {
        $exists = $this->findById($rec->id);
        if ($exists) {
            $this->update($rec);
        } else {
            $this->insert($rec);
        }
    }

    public function findById(string $id): ?Recommendation
    {
        $stmt = $this->db->prepare("SELECT * FROM recommendations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRowToEntity($row) : null;
    }

    public function getPending(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM recommendations WHERE status = 'pending' ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function getLatestForTicker(string $ticker): ?Recommendation
    {
        $stmt = $this->db->prepare("SELECT * FROM recommendations WHERE ticker = :ticker ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['ticker' => $ticker]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRowToEntity($row) : null;
    }

    private function insert(Recommendation $rec): void
    {
        $sql = "INSERT INTO recommendations (
            id, created_at, expires_at, action, portfolio_type, ticker, company_name,
            recommended_shares, current_price, estimated_cost, estimated_proceeds,
            order_type, limit_price, limit_valid_until, order_type_reason,
            narrative_summary, full_narrative, urgency, status,
            quality_tier, income_impact, goal_impact_percent
        ) VALUES (
            :id, :created_at, :expires_at, :action, :portfolio_type, :ticker, :company_name,
            :recommended_shares, :current_price, :estimated_cost, :estimated_proceeds,
            :order_type, :limit_price, :limit_valid_until, :order_type_reason,
            :narrative_summary, :full_narrative, :urgency, :status,
            :quality_tier, :income_impact, :goal_impact_percent
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $rec->id,
            'created_at' => $rec->createdAt->format('Y-m-d H:i:s'),
            'expires_at' => $rec->expiresAt->format('Y-m-d H:i:s'),
            'action' => $rec->action->value,
            'portfolio_type' => $rec->portfolioType->value,
            'ticker' => $rec->ticker,
            'company_name' => $rec->companyName,
            'recommended_shares' => $rec->recommendedShares,
            'current_price' => $rec->currentPrice,
            'estimated_cost' => $rec->estimatedCost,
            'estimated_proceeds' => $rec->estimatedProceeds,
            'order_type' => $rec->orderType->value,
            'limit_price' => $rec->limitPrice,
            'limit_valid_until' => $rec->limitValidUntil?->format('Y-m-d'),
            'order_type_reason' => $rec->orderTypeReason,
            'narrative_summary' => $rec->narrativeSummary,
            'full_narrative' => $rec->fullNarrative,
            'urgency' => $rec->urgency->value,
            'status' => $rec->status->value,
            'quality_tier' => $rec->qualityTier,
            'income_impact' => $rec->incomeImpact,
            'goal_impact_percent' => $rec->goalImpactPercent
        ]);
    }

    private function update(Recommendation $rec): void
    {
        $sql = "UPDATE recommendations SET
            status = :status,
            approved_at = :approved_at,
            denied_at = :denied_at,
            denial_reason = :denial_reason,
            executed_at = :executed_at
            WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $rec->status->value,
            'approved_at' => $rec->approvedAt?->format('Y-m-d H:i:s'),
            'denied_at' => $rec->deniedAt?->format('Y-m-d H:i:s'),
            'denial_reason' => $rec->denialReason,
            'executed_at' => $rec->executedAt?->format('Y-m-d H:i:s'),
            'id' => $rec->id
        ]);
    }

    private function mapRowToEntity(array $row): Recommendation
    {
        return new Recommendation(
            id: $row['id'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            expiresAt: new \DateTimeImmutable($row['expires_at']),
            action: TradeAction::from($row['action']),
            portfolioType: PortfolioType::from($row['portfolio_type']),
            ticker: $row['ticker'],
            companyName: $row['company_name'],
            recommendedShares: (int) $row['recommended_shares'],
            currentPrice: (float) $row['current_price'],
            estimatedCost: $row['estimated_cost'] ? (float) $row['estimated_cost'] : null,
            estimatedProceeds: $row['estimated_proceeds'] ? (float) $row['estimated_proceeds'] : null,
            orderType: OrderType::from($row['order_type']),
            limitPrice: $row['limit_price'] ? (float) $row['limit_price'] : null,
            limitValidUntil: $row['limit_valid_until'] ? new \DateTimeImmutable($row['limit_valid_until']) : null,
            orderTypeReason: $row['order_type_reason'],
            narrativeSummary: $row['narrative_summary'],
            fullNarrative: $row['full_narrative'] ?? '',
            urgency: Urgency::from($row['urgency']),
            status: RecommendationStatus::from($row['status']),
            qualityTier: $row['quality_tier'],
            incomeImpact: $row['income_impact'] ? (float) $row['income_impact'] : null,
            goalImpactPercent: $row['goal_impact_percent'] ? (float) $row['goal_impact_percent'] : null,
            approvedAt: $row['approved_at'] ? new \DateTimeImmutable($row['approved_at']) : null,
            deniedAt: $row['denied_at'] ? new \DateTimeImmutable($row['denied_at']) : null,
            denialReason: $row['denial_reason'],
            executedAt: $row['executed_at'] ? new \DateTimeImmutable($row['executed_at']) : null
        );
    }
}
