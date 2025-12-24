<?php

namespace SixGates\DataProviders;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class FinancialModelingPrepProvider implements DataProviderInterface
{
    public function __construct(
        private ClientInterface $client,
        private string $apiKey
    ) {
    }

    private function fetch(string $endpoint, array $query = []): array
    {
        try {
            $query['apikey'] = $this->apiKey;
            $response = $this->client->request('GET', $endpoint, ['query' => $query]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            // In a real app we'd log this or throw a custom exception
            // Propagate code (e.g. 402) for handling
            throw new \RuntimeException("FMP API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getIncomeStatement(string $ticker, int $limit = 5): array
    {
        return $this->fetch("income-statement", ['symbol' => $ticker, 'limit' => $limit, 'period' => 'annual']);
    }

    public function getBalanceSheet(string $ticker, int $limit = 5): array
    {
        return $this->fetch("balance-sheet-statement", ['symbol' => $ticker, 'limit' => $limit, 'period' => 'annual']);
    }

    public function getCashFlow(string $ticker, int $limit = 5): array
    {
        return $this->fetch("cash-flow-statement", ['symbol' => $ticker, 'limit' => $limit, 'period' => 'annual']);
    }

    public function getKeyMetrics(string $ticker, int $limit = 5): array
    {
        return $this->fetch("key-metrics", ['symbol' => $ticker, 'limit' => $limit, 'period' => 'annual']);
    }

    public function getRatios(string $ticker, int $limit = 5): array
    {
        return $this->fetch("ratios", ['symbol' => $ticker, 'limit' => $limit, 'period' => 'annual']);
    }

    public function getInsiderTrading(string $ticker): array
    {
        try {
            return $this->fetch("insider-trading", ['symbol' => $ticker, 'limit' => 100]);
        } catch (\RuntimeException $e) {
            // Insider endpoints often restricted or legacy.
            return [];
        }
    }

    public function getAnalystEstimates(string $ticker): array
    {
        return $this->fetch("analyst-estimates", ['symbol' => $ticker, 'period' => 'annual', 'limit' => 10]);
    }

    public function getHistoricalPrice(string $ticker): array
    {
        // Force V3 endpoint as stable/historical-price-full seems broken
        $data = $this->fetch("https://financialmodelingprep.com/api/v3/historical-price-full/{$ticker}");
        return $data['historical'] ?? $data;
    }

    public function getStockNews(string $ticker, int $limit = 50): array
    {
        try {
            return $this->fetch("news/stock", ['symbols' => $ticker, 'limit' => $limit]);
        } catch (\RuntimeException $e) {
            echo "NEWS FETCH ERROR: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function getQuote(string $ticker): array
    {
        return $this->fetch("quote", ['symbol' => $ticker]);
    }

    public function getSectorPerformance(): array
    {
        return $this->fetch("sector-performance");
    }
}
