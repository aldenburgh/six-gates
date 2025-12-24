<?php

namespace SixGates\Repositories;

use \PDO;
use SixGates\Entities\ExecutionLog;

class ExecutionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function save(ExecutionLog $log): void
    {
        $sql = "INSERT INTO execution_log (
            id, recommendation_id, ticker, action, portfolio_type,
            actual_shares, actual_price, commission, execution_date,
            broker, notes,
            shares_variance, shares_variance_percent,
            price_variance, price_variance_percent,
            total_variance, total_variance_percent,
            created_at,
            recommended_shares, recommended_price, recommended_order_type, recommended_total,
            actual_total
        ) VALUES (
            :id, :recommendation_id, :ticker, :action, :portfolio_type,
            :actual_shares, :actual_price, :commission, :execution_date,
            :broker, :notes,
            :shares_variance, :shares_variance_percent,
            :price_variance, :price_variance_percent,
            :total_variance, :total_variance_percent,
            :created_at,
            :rec_shares, :rec_price, :rec_order_type, :rec_total,
            :actual_total
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id' => $log->id,
            'recommendation_id' => $log->recommendationId,
            'ticker' => $log->ticker,
            'action' => $log->action->value,
            'portfolio_type' => $log->portfolioType->value,
            'actual_shares' => $log->actualShares,
            'actual_price' => $log->actualPrice,
            'commission' => $log->commission,
            'execution_date' => $log->executionDate->format('Y-m-d'),
            'broker' => $log->broker,
            'notes' => $log->notes,
            'shares_variance' => $log->sharesVariance,
            'shares_variance_percent' => $log->sharesVariancePercent,
            'price_variance' => $log->priceVariance,
            'price_variance_percent' => $log->priceVariancePercent,
            'total_variance' => $log->totalVariance,
            'total_variance_percent' => $log->totalVariancePercent,
            'created_at' => $log->createdAt->format('Y-m-d H:i:s'),
            'rec_shares' => $log->recommendedShares,
            'rec_price' => $log->recommendedPrice,
            'rec_order_type' => $log->recommendedOrderType,
            'rec_total' => $log->recommendedTotal,
            'actual_total' => $log->actualTotal
        ]);
    }
}
