<?php

namespace SixGates\Services\Data;

use SixGates\DataProviders\DataProviderInterface;

class EconomicDataService
{
    public function __construct(
        private DataProviderInterface $provider
    ) {
    }

    public function getFinancials(string $ticker, int $limit = 5): array
    {
        return [
            'income' => $this->provider->getIncomeStatement($ticker, $limit),
            'balance' => $this->provider->getBalanceSheet($ticker, $limit),
            'cashflow' => $this->provider->getCashFlow($ticker, $limit),
            'ratios' => $this->provider->getRatios($ticker, $limit),
            'key_metrics' => $this->provider->getKeyMetrics($ticker, $limit)
        ];
    }

    // Potentially helper methods for specific calculations like "getFreeCashFlow"
}
