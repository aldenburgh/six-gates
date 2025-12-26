<?php

namespace SixGates\Services\Data;

use SixGates\DataProviders\DataProviderInterface;

class MarketDataService
{
    public function __construct(
        private DataProviderInterface $provider
    ) {
    }

    public function getQuote(string $ticker): array
    {
        $quotes = $this->provider->getQuote($ticker);
        return $quotes[0] ?? [];
    }

    public function getHistoricalPrices(string $ticker): array
    {
        return $this->provider->getHistoricalPrice($ticker);
    }

    public function getProfile(string $ticker): ?array
    {
        return $this->provider->getCompanyProfile($ticker);
    }
}
