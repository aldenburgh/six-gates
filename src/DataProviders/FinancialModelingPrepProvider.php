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
        try {
            // Try standard endpoint without strict period
            return $this->fetch("key-metrics", ['symbol' => $ticker, 'limit' => $limit]);
        } catch (\RuntimeException $e) {
            try {
                // Fallback to ratios
                return $this->fetch("ratios", ['symbol' => $ticker, 'limit' => $limit]);
            } catch (\Exception $e2) {
                throw $e;
            }
        }
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
            return [];
        }
    }

    public function getAnalystEstimates(string $ticker): array
    {
        return $this->fetch("analyst-estimates", ['symbol' => $ticker, 'period' => 'annual', 'limit' => 10]);
    }

    public function getHistoricalPrice(string $ticker): array
    {
        // Stable API Historical
        $data = $this->fetch("historical-price-full", ['symbol' => $ticker]);
        return $data['historical'] ?? $data;
    }

    public function getStockNews(string $ticker, int $limit = 50): array
    {
        try {
            // Use full URL to bypass client base_uri (which is /stable)
            // Use stable endpoint which requires 'symbols' instead of 'tickers'
            return $this->fetch("https://financialmodelingprep.com/stable/news/stock", ['symbols' => $ticker, 'limit' => $limit]);
        } catch (\RuntimeException $e) {
            error_log("NEWS FETCH ERROR: " . $e->getMessage());
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

    public function getCompanyProfile(string $ticker): ?array
    {
        $data = $this->fetch("profile", ['symbol' => $ticker]);
        return $data[0] ?? null;
    }
}
