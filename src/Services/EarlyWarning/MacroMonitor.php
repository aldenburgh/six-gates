<?php

namespace SixGates\Services\EarlyWarning;

use SixGates\Services\Data\MarketDataService;

class MacroMonitor
{
    public function __construct(
        private MarketDataService $marketData
    ) {
    }

    public function getVixLevel(): float
    {
        $quote = $this->marketData->getQuote('^VIX');
        return $quote['price'] ?? 0.0;
    }

    public function getYieldSpread(): float
    {
        // 10Y - 2Y Spread
        $tenYear = $this->marketData->getQuote('10Y');
        $twoYear = $this->marketData->getQuote('2Y');

        $y10 = $tenYear['price'] ?? 0.0;
        $y2 = $twoYear['price'] ?? 0.0;

        if ($y10 == 0 || $y2 == 0) {
            return 0.0;
        }

        return round($y10 - $y2, 3);
    }

    public function getInflationRate(): float
    {
        // Mock constant since we didn't add CPI to MockDataProvider yet
        // In real app, fetch CPI series.
        return 3.5;
    }

    public function getRiskLevel(): string
    {
        // Mock logic for V7 stub
        // In real app, fetch VIX, Yield Curve via MarketDataService

        // $vix = $this->marketData->getQuote('^VIX'); 
        // For now, return 'low' or simulate based on placeholder

        return 'low';
    }
}
