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
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 0;

            // Handle 403 Forbidden or 401 Unauthorized (e.g. Free Tier Limits)
            if ($statusCode === 403 || $statusCode === 401) {
                error_log("FMP API Access Denied ($statusCode) for endpoint: $endpoint. Msg: " . $e->getMessage());
                return []; // Fail gracefully
            }

            throw new \RuntimeException("FMP API Error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            // Propagate other errors (e.g. connection issues)
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
            // v4 is required for insider trading now
            // Use statistics endpoint as it is stable and provides aggregated data
            return $this->fetch("insider-trading/statistics", ['symbol' => $ticker, 'limit' => 100]);
        } catch (\RuntimeException $e) {
            return [];
        }
    }

    public function getAnalystEstimates(string $ticker): array
    {
        return $this->fetch("analyst-estimates/{$ticker}", ['period' => 'annual', 'limit' => 10]);
    }

    public function getHistoricalPrice(string $ticker): array
    {
        // Stable API Historical
        $data = $this->fetch("historical-price-full/{$ticker}");
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
        return $this->fetch("quote/{$ticker}");
    }

    public function getSectorPerformance(): array
    {
        return $this->fetch("sector-performance");
    }

    public function getCompanyProfile(string $ticker): ?array
    {
        $data = $this->fetch("profile/{$ticker}");
        return $data[0] ?? null;
    }

    public function getESGData(string $ticker): ?array
    {
        try {
            // Use absolute URL as provided by user: https://financialmodelingprep.com/stable/esg-ratings
            $data = $this->fetch("https://financialmodelingprep.com/stable/esg-ratings", ['symbol' => $ticker]);
            return $data[0] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
