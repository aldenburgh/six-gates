<?php

namespace SixGates\DataProviders;

use Psr\Cache\CacheItemPoolInterface;

class CacheableProviderDecorator implements DataProviderInterface
{
    private array $ttlConfig;

    public function __construct(
        private DataProviderInterface $tweed,
        private CacheItemPoolInterface $cache,
        ?array $ttlConfig = null
    ) {
        $this->ttlConfig = $ttlConfig ?? [
            'income-statement' => 86400,
            'balance-sheet' => 86400,
            'cash-flow' => 86400,
            'key-metrics' => 86400,
            'ratios' => 86400,
            'insider-trading' => 43200,
            'analyst-estimates' => 43200,
            'historical-price' => 3600,
            'stock_news' => 1800,
            'quote' => 300,
            'sector-performance' => 3600,
        ];
    }

    private function getCached(string $key, string $type, callable $callback): array
    {
        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $data = $callback();
            $item->set($data);
            $item->expiresAfter($this->ttlConfig[$type] ?? 3600);
            $this->cache->save($item);
            return $data;
        }

        return $item->get();
    }

    public function getIncomeStatement(string $ticker, int $limit = 5): array
    {
        return $this->getCached(
            "fmp.income.{$ticker}.{$limit}",
            'income-statement',
            fn() => $this->tweed->getIncomeStatement($ticker, $limit)
        );
    }

    public function getBalanceSheet(string $ticker, int $limit = 5): array
    {
        return $this->getCached(
            "fmp.balance.{$ticker}.{$limit}",
            'balance-sheet',
            fn() => $this->tweed->getBalanceSheet($ticker, $limit)
        );
    }

    public function getCashFlow(string $ticker, int $limit = 5): array
    {
        return $this->getCached(
            "fmp.cash.{$ticker}.{$limit}",
            'cash-flow',
            fn() => $this->tweed->getCashFlow($ticker, $limit)
        );
    }

    public function getKeyMetrics(string $ticker, int $limit = 5): array
    {
        return $this->getCached(
            "fmp.metrics.{$ticker}.{$limit}",
            'key-metrics',
            fn() => $this->tweed->getKeyMetrics($ticker, $limit)
        );
    }

    public function getRatios(string $ticker, int $limit = 5): array
    {
        return $this->getCached(
            "fmp.ratios.{$ticker}.{$limit}",
            'ratios',
            fn() => $this->tweed->getRatios($ticker, $limit)
        );
    }

    public function getInsiderTrading(string $ticker): array
    {
        return $this->getCached(
            "fmp.insider.{$ticker}",
            'insider-trading',
            fn() => $this->tweed->getInsiderTrading($ticker)
        );
    }

    public function getAnalystEstimates(string $ticker): array
    {
        return $this->getCached(
            "fmp.estimates.{$ticker}",
            'analyst-estimates',
            fn() => $this->tweed->getAnalystEstimates($ticker)
        );
    }

    public function getHistoricalPrice(string $ticker): array
    {
        return $this->getCached(
            "fmp.price.{$ticker}",
            'historical-price',
            fn() => $this->tweed->getHistoricalPrice($ticker)
        );
    }

    public function getStockNews(string $ticker, int $limit = 50): array
    {
        return $this->getCached(
            "fmp.news.{$ticker}.{$limit}",
            'stock_news',
            fn() => $this->tweed->getStockNews($ticker, $limit)
        );
    }

    public function getQuote(string $ticker): array
    {
        return $this->getCached(
            "fmp.quote.{$ticker}",
            'quote',
            fn() => $this->tweed->getQuote($ticker)
        );
    }

    public function getSectorPerformance(): array
    {
        return $this->getCached(
            "fmp.sector",
            'sector-performance',
            fn() => $this->tweed->getSectorPerformance()
        );
    }

    public function getCompanyProfile(string $ticker): ?array
    {
        $item = $this->cache->getItem("fmp.profile.{$ticker}");

        if (!$item->isHit()) {
            $data = $this->tweed->getCompanyProfile($ticker);
            $item->set($data);
            $item->expiresAfter(86400);
            $this->cache->save($item);
            return $data;
        }

        return $item->get();
    }

    public function getESGData(string $ticker): ?array
    {
        $item = $this->cache->getItem("fmp.esg.{$ticker}");

        if (!$item->isHit()) {
            $data = $this->tweed->getESGData($ticker);
            $item->set($data);
            $item->expiresAfter(86400 * 7); // Cache for a week, changes slowly
            $this->cache->save($item);
            return $data;
        }

        return $item->get();
    }
    public function getEarningCallTranscript(string $ticker, ?int $year = null, ?int $quarter = null): ?array
    {
        $year = $year ?? (int) date('Y');
        $quarter = $quarter ?? ceil(date('n') / 3);
        $key = "fmp.transcript.{$ticker}.{$year}.{$quarter}";

        $item = $this->cache->getItem($key);

        if (!$item->isHit()) {
            $data = $this->tweed->getEarningCallTranscript($ticker, $year, $quarter);
            // Cache specific transcript forever (historical data doesn't change)
            if ($data) {
                $item->set($data);
                $item->expiresAfter(31536000); // 1 year
                $this->cache->save($item);
            }
            return $data;
        }

        return $item->get();
    }
}
