<?php

namespace SixGates\DataProviders;

interface DataProviderInterface
{
    public function getIncomeStatement(string $ticker, int $limit = 5): array;
    public function getBalanceSheet(string $ticker, int $limit = 5): array;
    public function getCashFlow(string $ticker, int $limit = 5): array;
    public function getKeyMetrics(string $ticker, int $limit = 5): array;
    public function getRatios(string $ticker, int $limit = 5): array;
    public function getInsiderTrading(string $ticker): array;
    public function getAnalystEstimates(string $ticker): array;
    public function getHistoricalPrice(string $ticker): array;
    public function getStockNews(string $ticker, int $limit = 50): array;
    public function getQuote(string $ticker): array;
    public function getSectorPerformance(): array;
    public function getCompanyProfile(string $ticker): ?array;
    public function getESGData(string $ticker): ?array;
    public function getEarningCallTranscript(string $ticker, ?int $year = null, ?int $quarter = null): ?array;
}
