<?php

namespace SixGates\DataProviders;

class MockDataProvider implements DataProviderInterface
{
    public function getIncomeStatement(string $ticker, int $limit = 5): array
    {
        return array_fill(0, $limit, [
            'date' => '2023-09-30',
            'netIncome' => 100000000000,
            'revenue' => 383000000000,
            'eps' => 6.13,
            'ebitda' => 125000000000,
            'interestExpense' => 3000000000, // Added
        ]);
    }



    public function getRatios(string $ticker, int $limit = 5): array
    {
        return array_fill(0, $limit, [
            'dividendYield' => 0.005,
            'payoutRatio' => 0.15,
            'priceToEarningsRatio' => 30.0, // Added
            'pegRatio' => 1.2,              // Added
            'priceToFreeCashFlowRatio' => 25.0, // Added
        ]);
    }

    public function getBalanceSheet(string $ticker, int $limit = 5): array
    {
        return array_fill(0, $limit, [
            'date' => '2023-09-30',
            'totalAssets' => 350000000000,
            'totalLiabilities' => 290000000000,
            'totalStockholdersEquity' => 60000000000,
            'cashAndCashEquivalents' => 30000000000,
            'totalDebt' => 100000000000,
            'netDebt' => 70000000000,
            'netReceivables' => 30000000000,
            'inventory' => 6000000000,
        ]);
    }

    public function getCashFlow(string $ticker, int $limit = 5): array
    {
        return array_fill(0, $limit, [
            'freeCashFlow' => 90000000000,
            'operatingCashFlow' => 110000000000,
            'capitalExpenditure' => -10000000000,
            'dividendsPaid' => -15000000000,
        ]);
    }



    public function getInsiderTrading(string $ticker): array
    {
        return [];
    }

    public function getKeyMetrics(string $ticker, int $limit = 5): array
    {
        return array_fill(0, $limit, [
            'roic' => 0.25,
            'returnOnInvestedCapital' => 0.25, // FMP alias
            'roicTTM' => 0.25,
            'peRatio' => 30.0,
            'debtToEquity' => 1.5,
            'interestCoverage' => 20.0,
        ]);
    }

    public function getHistoricalPrice(string $ticker): array
    {
        // Simple decline from 250 to 200
        $prices = [];
        for ($i = 0; $i < 255; $i++) {
            $prices[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'close' => 200.0 + ($i * 0.1),
                'adjClose' => 200.0 + ($i * 0.1),
            ];
        }
        return $prices;
    }

    public function getAnalystEstimates(string $ticker): array
    {
        return array_fill(0, 5, [
            'date' => '2025-09-30',
            'estimatedEpsAvg' => 7.0,
            'estimatedRevenueAvg' => 450000000000,
        ]);
    }

    public function getStockNews(string $ticker, int $limit = 50): array
    {
        return [];
    }

    public function getQuote(string $ticker): array
    {
        // Mock Quote Data
        $base = [
            'symbol' => $ticker,
            'price' => 150.00,
            'changesPercentage' => 1.5,
            'change' => 2.25,
            'dayLow' => 148.00,
            'dayHigh' => 152.00,
            'yearHigh' => 180.00,
            'yearLow' => 120.00,
            'marketCap' => 2500000000000,
            'priceAvg50' => 145.00,
            'priceAvg200' => 140.00,
            'volume' => 50000000,
            'avgVolume' => 60000000,
            'exchange' => 'NASDAQ',
            'timestamp' => time()
        ];

        // Specific mocks for Macro
        if ($ticker === '^VIX') {
            $base['price'] = 18.50; // Moderate Volatility
            $base['name'] = 'CBOE Volatility Index';
        }

        if ($ticker === '10Y') {
            $base['price'] = 4.20; // 4.2% Yield
            $base['name'] = '10 Year Treasury Note';
        }

        if ($ticker === '2Y') {
            $base['price'] = 4.50; // 4.5% Yield (Inverted Curve)
            $base['name'] = '2 Year Treasury Note';
        }

        return [$base];
    }

    public function getSectorPerformance(): array
    {
        return [];
    }

    public function getCompanyProfile(string $ticker): ?array
    {
        return [
            'companyName' => 'Apple Inc',
            'sector' => 'Technology',
            'industry' => 'Consumer Electronics',
            'price' => 200.0,
            'beta' => 1.2,
            'description' => 'Mock Description',
        ];
    }
}
