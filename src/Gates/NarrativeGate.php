<?php

namespace SixGates\Gates;

use SixGates\DataProviders\DataProviderInterface;

class NarrativeGate implements GateInterface
{
    private array $thresholds;

    public function __construct(array $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    public function analyze(string $ticker, DataProviderInterface $provider): GateResult
    {
        // Estimate Revisions
        $estimates = $provider->getAnalystEstimates($ticker);

        // Institutional Flows (13F)
        // FMP has institutional-holder or similar.
        // Mock provider currently returns empty for this.
        // We can simulate if needed or rely on estimates for now.

        if (empty($estimates)) {
            return new GateResult('gate_5', false, [], 'Insufficient data');
        }

        // Calculate Revision Trend
        // Compare estimates from X months ago vs now?
        // FMP estimates endpoint returns a list of future estimates.
        // It often doesn't give historical changes to those estimates directly in one call without time travel.
        // But it gives 'date' of estimate.
        // Let's assume we get a series of estimates for the SAME period produced at DIFFERENT times.
        // Or simpler: Compare Current Year Estimate vs Next Year? That's growth.
        // We want "Revision" - i.e. did analysts upgrade?
        // If FMP only gives current snapshot, we can't track revision without our own history (database).
        // Since we store 'analysis_results', in real app we compare against OUR last run.
        // For MVP, if we lack history, we can't fully calculate revision score.
        // We return 'NEUTRAL' or skip.

        // However, 'numberAnalystsEstimatedIeps' gives conviction.

        // Mock Implementation for Logic:
        // Let's assume we track 13F Sentiment or similar via Mock.
        // Input: "Estimate revisions: Track analyst EPS estimate changes over 3-6 months"

        // Let's return a "Narrative Score" based on available data.

        $metrics = [
            'analyst_count' => $estimates[0]['numAnalystsEps'] ?? 0,
            'avg_eps_estimate' => $estimates[0]['epsAvg'] ?? 0,
            'revision_trend' => 0 // Placeholder
        ];

        // Classification logic as per prompt:
        // Improving + Negative Narrative = EARLY
        // Improving + Positive Narrative = NEUTRAL
        // etc.
        // We need 'Narrative Sentiment'. News API?
        // Let's check 'stock_news' sentiment if available.
        // Calculate Sentiment from News content
        $news = $provider->getStockNews($ticker, 10);
        $sentimentScore = 0;
        $scoredArticles = 0;

        foreach ($news as $article) {
            $text = ($article['title'] ?? '') . ' ' . ($article['text'] ?? '');
            if (empty($text))
                continue;

            $score = $this->calculateSentiment($text);
            $sentimentScore += $score;
            $scoredArticles++;
        }

        $avgSentiment = $scoredArticles > 0 ? $sentimentScore / $scoredArticles : 0;
        $metrics['sentiment_score'] = $avgSentiment;
        $metrics['news_count'] = count($news);

        // Determine Status based on Sentiment
        if ($avgSentiment > 0.5)
            $status = 'OPTIMISTIC';
        elseif ($avgSentiment < -0.5)
            $status = 'PESSIMISTIC';
        else
            $status = 'NEUTRAL';



        return new GateResult(
            'gate_5',
            true, // Sizing gate, always passes unless error
            $metrics,
            null,
            ['status' => $status]
        );
    }
    private function calculateSentiment(string $text): float
    {
        $text = strtolower($text);

        $positive = ['upgrade', 'buy', 'growth', 'record', 'beat', 'profit', 'bull', 'surge', 'jump', 'gain', 'strong', 'outperform', 'hike', 'positive', 'rally'];
        $negative = ['downgrade', 'sell', 'decline', 'miss', 'loss', 'bear', 'plunge', 'drop', 'fall', 'weak', 'underperform', 'cut', 'negative', 'crash', 'warn'];

        $score = 0;
        foreach ($positive as $word) {
            if (strpos($text, $word) !== false)
                $score += 1;
        }
        foreach ($negative as $word) {
            if (strpos($text, $word) !== false)
                $score -= 1;
        }

        return max(-2, min(2, $score));
    }
}
