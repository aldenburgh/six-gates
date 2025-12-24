<?php

namespace SixGates\Moat;

use SixGates\DataProviders\AnthropicProvider;
use SixGates\DataProviders\DataProviderInterface;

class LLMMoatAssessor
{
    public function __construct(
        private AnthropicProvider $llm,
        private DataProviderInterface $dataProvider
    ) {
    }

    public function assess(string $ticker): MoatAssessment
    {
        // Gather necessary data for context
        $profile = $this->dataProvider->getQuote($ticker)[0] ?? [];
        $metrics = $this->dataProvider->getKeyMetrics($ticker, 5);
        $ratios = $this->dataProvider->getRatios($ticker, 5);

        // Prepare prompt data
        $companyName = $profile['name'] ?? $ticker;
        $sector = $profile['sector'] ?? 'Unknown';
        $industry = $profile['industry'] ?? 'Unknown'; // Note: API might not return industry in quote, strictly usually in profile

        // Use Ratios for Gross Margin
        $margins = array_column($ratios, 'grossProfitMargin');
        $avgMargin = !empty($margins) ? array_sum($margins) / count($margins) : 0;
        $marginTrend = $this->calculateTrend($margins);

        // Use Metrics for ROIC
        $roics = array_column($metrics, 'returnOnInvestedCapital');
        $avgRoic = !empty($roics) ? array_sum($roics) / count($roics) : 0;

        // Note: FMP doesn't give 'Customer Concentration' or 'Market Share' easily via API without specialized endpoints.
        // We will ask the LLM to infer from its knowledge base + the financial signals we provide.

        $systemPrompt = <<<PROMPT
You are an expert business analyst specializing in competitive advantage assessment.
Analyze the provided company data and determine:
1. Primary moat type (brand, network_effects, switching_costs, cost_advantages, efficient_scale, intangible_assets, or none)
2. Secondary moats if any
3. Durability rating (high, medium, low, none)
4. Evidence supporting the moat assessment
5. Threats that could erode the moat

Be conservative. Many companies have no durable moat. 
A moat must be STRUCTURAL, not just current market position.
PROMPT;

        $userPrompt = <<<PROMPT
Analyze this company for competitive moat:

Company: {$companyName} ({$ticker})
Sector: {$sector}
Industry: {$industry}

Financial Indicators:
- Gross Margin (5yr avg): " . number_format($avgMargin * 100, 2) . "%
- Gross Margin Trend: {$marginTrend}
- ROIC (5yr avg): " . number_format($avgRoic * 100, 2) . "%

Current Price: {$profile['price']}
Market Cap: {$profile['marketCap']}

Please use your internal knowledge about the company's business model to supplement these financials.

Respond in PURE JSON format (no markdown):
{
    "primary_moat": "string or null",
    "secondary_moats": ["array"],
    "durability": "high|medium|low|none",
    "evidence": ["array of evidence points"],
    "threats": ["array of threats"],
    "confidence": 0.0-1.0,
    "reasoning": "brief explanation"
}
PROMPT;

        try {
            $jsonResponse = $this->llm->generate($systemPrompt, $userPrompt);
            // Clean markdown if present
            $jsonResponse = str_replace(['```json', '```'], '', $jsonResponse);
            $data = json_decode($jsonResponse, true);

            if (!$data) {
                throw new \RuntimeException("Failed to decode LLM response");
            }

            return new MoatAssessment(
                moatType: $data['primary_moat'] === 'null' ? null : $data['primary_moat'],
                secondaryMoats: $data['secondary_moats'] ?? [],
                durability: $data['durability'] ?? 'none',
                moatEvidence: $data['evidence'] ?? [],
                moatThreats: $data['threats'] ?? [],
                assessmentMethod: 'llm',
                humanOverride: null,
                confidenceScore: (float) ($data['confidence'] ?? 0.0)
            );

        } catch (\Exception $e) {
            // Fallback for LLM failure
            return new MoatAssessment(
                moatType: null,
                secondaryMoats: [],
                durability: 'none',
                moatEvidence: ['Error retrieving assessment: ' . $e->getMessage()],
                moatThreats: [],
                assessmentMethod: 'llm_failed',
                humanOverride: null,
                confidenceScore: 0.0
            );
        }
    }

    private function calculateTrend(array $values): string
    {
        if (count($values) < 2)
            return 'stable';
        $start = end($values); // Oldest
        $end = reset($values); // Newest
        if ($end > $start * 1.05)
            return 'expanding';
        if ($end < $start * 0.95)
            return 'contracting';
        return 'stable';
    }
}
