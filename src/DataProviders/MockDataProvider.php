<?php

namespace SixGates\DataProviders;

class MockDataProvider implements DataProviderInterface
{
    private array $mockData;

    public function __construct()
    {
        $this->mockData = $this->loadMockData();
    }

    private function loadMockData(): array
    {
        // Basic AAPL-like data that passes most gates for testing
        return [
            'income' => [
                ['revenue' => 383285000000, 'operatingIncome' => 114301000000, 'netIncome' => 96995000000, 'interestExpense' => 3933000000, 'ebitda' => 125820000000, 'date' => '2023-09-30'],
                ['revenue' => 394328000000, 'operatingIncome' => 119437000000, 'netIncome' => 99803000000, 'interestExpense' => 2931000000, 'ebitda' => 130541000000, 'date' => '2022-09-30'],
                ['revenue' => 365817000000, 'operatingIncome' => 108949000000, 'netIncome' => 94680000000, 'interestExpense' => 2645000000, 'ebitda' => 120233000000, 'date' => '2021-09-30'],
                ['revenue' => 274515000000, 'operatingIncome' => 66288000000, 'netIncome' => 57411000000, 'interestExpense' => 2873000000, 'ebitda' => 77344000000, 'date' => '2020-09-30'],
                ['revenue' => 260174000000, 'operatingIncome' => 63930000000, 'netIncome' => 55256000000, 'interestExpense' => 3576000000, 'ebitda' => 76477000000, 'date' => '2019-09-30'],
            ],
            'balance' => [
                ['totalAssets' => 352583000000, 'netReceivables' => 29508000000, 'shortTermDebt' => 15613000000, 'longTermDebt' => 95281000000, 'date' => '2023-09-30'],
                ['totalAssets' => 352755000000, 'netReceivables' => 28184000000, 'shortTermDebt' => 9982000000, 'longTermDebt' => 98959000000, 'date' => '2022-09-30'],
                ['totalAssets' => 351002000000, 'netReceivables' => 26278000000, 'shortTermDebt' => 6000000000, 'longTermDebt' => 109106000000, 'date' => '2021-09-30'],
                ['totalAssets' => 323888000000, 'netReceivables' => 16120000000, 'shortTermDebt' => 13769000000, 'longTermDebt' => 98667000000, 'date' => '2020-09-30'],
                ['totalAssets' => 338516000000, 'netReceivables' => 22926000000, 'shortTermDebt' => 10260000000, 'longTermDebt' => 91807000000, 'date' => '2019-09-30'],
            ],
            'cash' => [
                ['freeCashFlow' => 99584000000, 'operatingCashFlow' => 110543000000, 'date' => '2023-09-30'],
                ['freeCashFlow' => 111443000000, 'operatingCashFlow' => 122151000000, 'date' => '2022-09-30'],
                ['freeCashFlow' => 92953000000, 'operatingCashFlow' => 104038000000, 'date' => '2021-09-30'],
                ['freeCashFlow' => 73365000000, 'operatingCashFlow' => 80674000000, 'date' => '2020-09-30'],
                ['freeCashFlow' => 58896000000, 'operatingCashFlow' => 69391000000, 'date' => '2019-09-30'],
            ],
            'metrics' => [
                ['roic' => 0.28, 'netDebtToEBITDA' => 0.8, 'date' => '2023-09-30'],
                ['roic' => 0.30, 'netDebtToEBITDA' => 0.7, 'date' => '2022-09-30'],
                ['roic' => 0.29, 'netDebtToEBITDA' => 0.9, 'date' => '2021-09-30'],
                ['roic' => 0.20, 'netDebtToEBITDA' => 1.1, 'date' => '2020-09-30'],
                ['roic' => 0.19, 'netDebtToEBITDA' => 1.2, 'date' => '2019-09-30'],
            ],
            'ratios' => [
                ['priceToEarningsRatio' => 28.5, 'pegRatio' => 1.2, 'priceToFreeCashFlowsRatio' => 25.0, 'dividendYield' => 0.005, 'date' => '2023-09-30'],
                ['priceToEarningsRatio' => 25.0, 'pegRatio' => 1.1, 'priceToFreeCashFlowsRatio' => 22.0, 'dividendYield' => 0.006, 'date' => '2022-09-30'],
                ['priceToEarningsRatio' => 30.0, 'pegRatio' => 1.5, 'priceToFreeCashFlowsRatio' => 28.0, 'dividendYield' => 0.005, 'date' => '2021-09-30'],
                ['priceToEarningsRatio' => 35.0, 'pegRatio' => 2.0, 'priceToFreeCashFlowsRatio' => 30.0, 'dividendYield' => 0.006, 'date' => '2020-09-30'],
                ['priceToEarningsRatio' => 20.0, 'pegRatio' => 1.0, 'priceToFreeCashFlowsRatio' => 18.0, 'dividendYield' => 0.007, 'date' => '2019-09-30'],
            ],
            'estimate' => [
                ['date' => '2024-01-01', 'estimatedEpsAvg' => 6.50, 'numberAnalystsEstimatedIeps' => 30],
            ],
            'quote' => [
                'price' => 175.50,
                'pe' => 28.5,
            ]
        ];
    }

    public function getIncomeStatement(string $ticker, int $limit = 5): array
    {
        return array_slice($this->mockData['income'], 0, $limit);
    }
    public function getBalanceSheet(string $ticker, int $limit = 5): array
    {
        return array_slice($this->mockData['balance'], 0, $limit);
    }
    public function getCashFlow(string $ticker, int $limit = 5): array
    {
        return array_slice($this->mockData['cash'], 0, $limit);
    }
    public function getKeyMetrics(string $ticker, int $limit = 5): array
    {
        return array_slice($this->mockData['metrics'], 0, $limit);
    }
    public function getRatios(string $ticker, int $limit = 5): array
    {
        return array_slice($this->mockData['ratios'] ?? [], 0, $limit);
    }
    public function getInsiderTrading(string $ticker): array
    {
        return [
            ['transactionDate' => '2023-11-15', 'transactionType' => 'S-Sale', 'securitiesTransacted' => 10000, 'price' => 180.00],
            ['transactionDate' => '2023-10-01', 'transactionType' => 'P-Purchase', 'securitiesTransacted' => 5000, 'price' => 170.00],
            ['transactionDate' => '2023-01-15', 'transactionType' => 'S-Sale', 'securitiesTransacted' => 2000, 'price' => 150.00],
        ];
    }
    public function getAnalystEstimates(string $ticker): array
    {
        return $this->mockData['estimate'];
    }
    public function getHistoricalPrice(string $ticker): array
    {
        return [];
    }
    public function getStockNews(string $ticker, int $limit = 50): array
    {
        return [];
    }
    public function getQuote(string $ticker): array
    {
        return [$this->mockData['quote']];
    }
    public function getSectorPerformance(): array
    {
        return [];
    }
}
