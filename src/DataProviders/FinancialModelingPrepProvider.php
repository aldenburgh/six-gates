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

            // Handle 403 Forbidden, 401 Unauthorized, 402 Payment Required, or 404 Not Found
            if (in_array($statusCode, [401, 402, 403, 404])) {
                // Log warning for restricted access but debug for 404? 
                // For now, consistent silent fail or log is better than crash.
                // error_log("FMP API Status $statusCode for $endpoint");
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
        return $this->fetch("analyst-estimates", ['symbol' => $ticker, 'period' => 'annual', 'limit' => 10]);
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
        $data = $this->fetch("profile", ['symbol' => $ticker]);
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

    public function getEarningCallTranscript(string $ticker, ?int $year = null, ?int $quarter = null): ?array
    {
        try {
            // Case 1: Specific Year/Quarter requested
            if ($year && $quarter) {
                $data = $this->fetch("earning-call-transcript", [
                    'symbol' => $ticker,
                    'year' => $year,
                    'quarter' => $quarter
                ]);
                return $data[0] ?? null; // FMP returns array of objects, we want the first/only one
            }

            // Case 2: Auto-detect (Fallback Logic)
            // Start from current quarter and look back up to 4 quarters
            $currentYear = (int) date('Y');
            $currentQuarter = (int) ceil(date('n') / 3);

            for ($i = 0; $i < 4; $i++) {
                // Calculate target quarter
                $targetYear = $currentYear;
                $targetQuarter = $currentQuarter - $i;

                // Handle year rollback
                while ($targetQuarter < 1) {
                    $targetQuarter += 4;
                    $targetYear--;
                }

                $data = $this->fetch("earning-call-transcript", [
                    'symbol' => $ticker,
                    'year' => $targetYear,
                    'quarter' => $targetQuarter
                ]);

                // Check if we got a valid transcript
                // Accessing index 0 because FMP returns [ { ... } ]
                if (!empty($data) && isset($data[0]['content'])) {
                    return $data[0];
                }
            }

            return null; // No transcript found in last year

        } catch (\Exception $e) {
            // Graceful failure as requested
            return null;
        }
    }
}
