<?php

namespace SixGates\MarketContext;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Utils\Statistics;

class MarketContextAssessor
{
    public function __construct(
        private array $thresholds // ['pe_high' => 25, 'volatility_high' => 30]
    ) {
    }

    public function assess(DataProviderInterface $provider): MarketContext
    {
        // Use SPY as proxy for S&P 500
        $ticker = 'SPY';

        // 1. Trend Analysis (200 SMA) using Quote
        // FMP Quote endpoint provides priceAvg200 directly.
        $quote = $provider->getQuote($ticker);

        if (empty($quote)) {
            // Fallback: If SPY fails (rare), assume neutral/bullish default to avoid blocking
            // or re-throw if critical. Let's return a default context.
            return new MarketContext(
                MarketContext::PHASE_BULL,
                50,
                0.0,
                ['error' => 'SPY data unavailable']
            );
        }

        $currentPrice = $quote[0]['price'] ?? 0;
        $sma200 = $quote[0]['priceAvg200'] ?? $currentPrice;

        // 2. Valuation (PE) using SPY Ratios (ETF ratios might not be perfect PE proxy but acceptable)
        // Or we use 'sector-pe' if available, but let's try SPY TTM PE.
        $ratios = $provider->getRatios($ticker, 1);
        $pe = $ratios[0]['priceEarningsRatio'] ?? 20.0; // Default to historical avg

        // 3. Volatility (VIX proxy?)
        // Can't easily get ^VIX via provider quote as seen in curl.
        // Can estimate vol from SPY daily returns?
        // Let's assume neutral volatility if data missing.

        // Determine Phase
        // Bull: Price > SMA200
        // Bear: Price < SMA200
        // Nuance: Distribution if Price < SMA50 but > SMA200? keeping it simple for V3 MVP.

        $phase = ($currentPrice > $sma200) ? MarketContext::PHASE_BULL : MarketContext::PHASE_BEAR;

        // If PE is extreme (>25), maybe Distribution/Bubble risk
        if ($pe > 25 && $phase === MarketContext::PHASE_BULL) {
            // "Heated Bull" -> maybe Distribution start?
            // Keep as Bull but raise risk score.
        }

        // Risk Score
        // Base 50
        $riskScore = 50;
        if ($phase === MarketContext::PHASE_BEAR)
            $riskScore += 20;
        if ($pe > 25)
            $riskScore += 10;
        if ($pe < 15)
            $riskScore -= 10; // Cheap

        // Discount Rate Adjustment
        // If Risk is High, we demand higher return (wacc + adjustment)
        $adjustment = 0.0;
        if ($riskScore > 70)
            $adjustment = 0.02; // +2% hurdle
        if ($riskScore < 40)
            $adjustment = -0.01; // -1% hurdle (accommodative)

        return new MarketContext(
            $phase,
            $riskScore,
            $adjustment,
            [
                'spy_price' => $currentPrice,
                'spy_sma200' => $sma200,
                'market_pe' => $pe
            ]
        );
    }
}
