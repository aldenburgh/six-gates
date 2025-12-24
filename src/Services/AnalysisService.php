<?php

namespace SixGates\Services;

use SixGates\DataProviders\DataProviderInterface;
use SixGates\Scoring\AnalysisResult;
use SixGates\Scoring\PositionSizer;
use SixGates\Scoring\QualityTierClassifier;
use SixGates\Scoring\SixGatesScorer;
use SixGates\MarketContext\MarketContextAssessor;

class AnalysisService
{
    private DataProviderInterface $provider;
    private SixGatesScorer $scorer;
    private QualityTierClassifier $classifier;
    private PositionSizer $sizer;
    private MarketContextAssessor $contextAssessor;
    private array $config;
    private \SixGates\DataProviders\AnthropicProvider $anthropic;

    public function __construct(
        DataProviderInterface $provider,
        SixGatesScorer $scorer,
        QualityTierClassifier $classifier,
        PositionSizer $sizer,
        MarketContextAssessor $contextAssessor,
        array $config,
        \SixGates\DataProviders\AnthropicProvider $anthropic
    ) {
        $this->provider = $provider;
        $this->scorer = $scorer;
        $this->classifier = $classifier;
        $this->sizer = $sizer;
        $this->contextAssessor = $contextAssessor;
        $this->config = $config;
        $this->anthropic = $anthropic;
    }

    public function analyze(string $ticker): AnalysisResult
    {
        // 1. Fetch Income Statement (Validation & Data check)
        // Profile/Quote endpoints are giving Legacy/Retention errors, so we rely on financial data availability.
        $financials = $this->provider->getIncomeStatement($ticker, 1);
        if (empty($financials)) {
            throw new \RuntimeException("Could not fetch financials for $ticker - Ticker may not exist or API error");
        }

        // Mock profile object if needed
        $profile = [
            'symbol' => $ticker,
            'companyName' => $ticker, // Fallback
            'description' => 'Description unavailable',
            'industry' => 'Unknown',
            'sector' => 'Unknown'
        ];

        // 2. Assess Market Context
        try {
            $marketContext = $this->contextAssessor->assess($this->provider);
        } catch (\Exception $e) {
            // Fallback
            $marketContext = null;
        }

        // 3. Configure Scorer with Gates
        $thresholds = $this->config['thresholds'];

        $this->scorer->resetGates();

        // Gate 1: Capital Allocation
        $this->scorer->addGate(new \SixGates\Gates\CapitalAllocationGate($thresholds['gate_1']));

        // Gate 1.5: Moat Assessment (LLM)
        $llmMoatAssessor = new \SixGates\Moat\LLMMoatAssessor($this->anthropic, $this->provider);
        $this->scorer->addGate(new \SixGates\Gates\MoatAssessmentGate($thresholds['gate_1_5'], $llmMoatAssessor));

        // Gate 2: Economic Engine
        $this->scorer->addGate(new \SixGates\Gates\EconomicEngineGate($thresholds['gate_2']));

        // Gate 2.5: Capital Structure
        $this->scorer->addGate(new \SixGates\Gates\CapitalStructureGate($thresholds['gate_2_5']));

        // Gate 2.75: Reinvestment Runway
        $this->scorer->addGate(new \SixGates\Gates\ReinvestmentRunwayGate($thresholds['gate_2_75']));

        // Gate 3: Cash Integrity
        $this->scorer->addGate(new \SixGates\Gates\CashIntegrityGate($thresholds['gate_3']));

        // Gate 3.5: Complexity Filter
        $this->scorer->addGate(new \SixGates\Gates\ComplexityFilterGate($thresholds['gate_3_5']));

        // Gate 4: Valuation
        $this->scorer->addGate(new \SixGates\Gates\ValuationGate($thresholds['gate_4']));

        // Gate 5: Narrative
        $this->scorer->addGate(new \SixGates\Gates\NarrativeGate($thresholds['gate_5']));

        // 4. Run Scoring
        $result = $this->scorer->score($ticker, $this->provider);

        // 5. Classify Quality
        $tier = $this->classifier->classify($result);

        // 6. Position Sizing
        $contextArray = $marketContext ? ['phase' => $marketContext->phase, 'risk' => $marketContext->riskScore] : [];
        $size = $this->sizer->calculate($tier, $marketContext);

        return $result->withTierAndSize($tier, $size, $contextArray);
    }
}
