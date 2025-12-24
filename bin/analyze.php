<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;
use SixGates\Repositories\StockRepository;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\DataProviders\CacheableProviderDecorator;
use SixGates\Gates\EconomicEngineGate;
use SixGates\Gates\CashIntegrityGate;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use GuzzleHttp\Client;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Config Loading (Manual for Phase 1)
$config = [
    'database' => require __DIR__ . '/../config/database.php',
    'api' => require __DIR__ . '/../config/api.php',
    'thresholds' => require __DIR__ . '/../config/thresholds.php',
];

echo "Six Gates Investment Screening System - Phase 1\n";
echo "-----------------------------------------------\n";

// Dependency Injection
try {
    // 1. Database - Skip for now if we don't have a real DB connection string, 
    // or wrap in try-catch to allow running CLI without DB for testing Gates logic
    // But repository depends on it. 
    // Let's assume for this "Basic CLI" we want to test the Gates primarily.
    // We'll mock the repository usage or warn if DB fails.

    // 2. Data Provider

    $httpClient = new Client([
        'base_uri' => $config['api']['fmp']['base_url'] . '/',
        'timeout' => 10.0
    ]);
    $fmpProvider = new FinancialModelingPrepProvider($httpClient, $config['api']['fmp']['api_key']);

    // Anthropic Provider for Moat
    $anthropicProvider = new \SixGates\DataProviders\AnthropicProvider(
        $httpClient,
        $config['api']['anthropic']['api_key'],
        $config['api']['anthropic']['model']
    );

    $cache = new FilesystemAdapter('six_gates', 3600, __DIR__ . '/../storage/cache');
    $dataProvider = new CacheableProviderDecorator($fmpProvider, $cache);

    // echo "NOTICE: Using Mock Data Provider (FMP Key Issue)\n";
    // $dataProvider = new \SixGates\DataProviders\MockDataProvider();

    // 3. Gates
    $economicGate = new EconomicEngineGate($config['thresholds']['gate_2']);
    $cashGate = new CashIntegrityGate($config['thresholds']['gate_3']);

    // Parse Arguments (Simple version)
    $shortopts = "";
    $longopts = ["ticker:", "force"];
    $options = getopt($shortopts, $longopts);

    $ticker = $options['ticker'] ?? 'AAPL'; // Default to AAPL

    echo "Analyzing Ticker: $ticker\n";

    // Gate 2
    echo "\n[Gate 2] Economic Engine:\n";
    $result2 = $economicGate->analyze($ticker, $dataProvider);
    if ($result2->passed) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ ({$result2->killReason})\n";
    }
    dumpMetrics($result2->metrics);

    // Gate 2.5
    echo "\n[Gate 2.5] Capital Structure:\n";
    $capitalGate = new \SixGates\Gates\CapitalStructureGate($config['thresholds']['gate_2_5']);
    $result25 = $capitalGate->analyze($ticker, $dataProvider);
    if ($result25->passed) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ ({$result25->killReason})\n";
    }
    dumpMetrics($result25->metrics);

    // Gate 3
    echo "\n[Gate 3] Cash Integrity:\n";
    $result3 = $cashGate->analyze($ticker, $dataProvider);
    if ($result3->passed) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ ({$result3->killReason})\n";
    }
    dumpMetrics($result3->metrics);

    // Gate 4
    echo "\n[Gate 4] Valuation:\n";
    $valGate = new \SixGates\Gates\ValuationGate($config['thresholds']['gate_4']);
    $result4 = $valGate->analyze($ticker, $dataProvider);
    if ($result4->passed) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ (Overvalued: {$result4->killReason})\n";
    }
    dumpMetrics($result4->metrics);

    // Gate 1
    echo "\n[Gate 1] Capital Allocation:\n";
    $capAllocGate = new \SixGates\Gates\CapitalAllocationGate($config['thresholds']['gate_1']);
    $result1 = $capAllocGate->analyze($ticker, $dataProvider);
    if ($result1->passed) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ ({$result1->killReason})\n";
    }
    dumpMetrics($result1->metrics);

    // Gate 5
    echo "\n[Gate 5] Narrative Arbitrage:\n";
    $narrativeGate = new \SixGates\Gates\NarrativeGate($config['thresholds']['gate_5']);
    $result5 = $narrativeGate->analyze($ticker, $dataProvider);
    echo "STATUS: " . ($result5->details['status'] ?? 'N/A') . "\n";
    dumpMetrics($result5->metrics);

    // Gate 1.5 (Moat)
    echo "\n[Gate 1.5] Moat Assessment:\n";
    $llmMoatAssessor = new \SixGates\Moat\LLMMoatAssessor($anthropicProvider, $dataProvider);
    $moatGate = new \SixGates\Gates\MoatAssessmentGate($config['thresholds']['gate_1_5'], $llmMoatAssessor);
    $result15 = $moatGate->analyze($ticker, $dataProvider);
    echo "Durability: " . ($result15->details['moat_durability'] ?? 'N/A') . "\n";
    echo "Type: " . ($result15->metrics['moat_type'] ?? 'None') . "\n";

    // Gate 2.75 (Runway)
    echo "\n[Gate 2.75] Reinvestment Runway:\n";
    $runwayGate = new \SixGates\Gates\ReinvestmentRunwayGate($config['thresholds']['gate_2_75']);
    $result275 = $runwayGate->analyze($ticker, $dataProvider);
    echo "Growth Category: " . ($result275->details['runway_category'] ?? 'N/A') . "\n";
    dumpMetrics($result275->metrics);

    // Gate 3.5 (Complexity)
    echo "\n[Gate 3.5] Complexity Filter:\n";
    $complexityGate = new \SixGates\Gates\ComplexityFilterGate($config['thresholds']['gate_3_5']);
    $result35 = $complexityGate->analyze($ticker, $dataProvider);
    if ($result35->passed) {
        echo "PASS ✅ (Not Too Hard)\n";
    } else {
        echo "FAIL ❌ (Too Hard)\n";
    }
    dumpMetrics($result35->metrics);

    // Market Context
    echo "\n[Market Context] Cycle Analysis:\n";
    $marketContextAssessor = new \SixGates\MarketContext\MarketContextAssessor($config['thresholds']['market']);
    // Note: MarketContext requires fetching SPY data.
    // If user plan restricts SPY, this might fail (402).
    // We wrap in try-catch to not kill the ticker analysis.
    try {
        $context = $marketContextAssessor->assess($dataProvider);
        echo "Phase: " . strtoupper($context->phase) . "\n";
        echo "Risk Score: " . $context->riskScore . "/100\n";
        echo "Discount Adj: " . ($context->discountRateAdjustment * 100) . "%\n";
    } catch (\Exception $e) {
        echo "Unable to assess market context: " . $e->getMessage() . "\n";
        $context = null;
    }



    // 4. Quality Tier & Sizing


    // Full Scoring
    echo "\n-----------------------------------------------\n";
    echo "Full Analysis Run:\n";
    echo "Ticker: $ticker\n";


    $scorer = new \SixGates\Scoring\SixGatesScorer();
    $scorer->addGate($capAllocGate); // Gate 1
    $scorer->addGate($economicGate); // Gate 2
    $scorer->addGate($capitalGate);  // Gate 2.5
    $scorer->addGate($cashGate);     // Gate 3
    $scorer->addGate($valGate);      // Gate 4
    $scorer->addGate($narrativeGate);// Gate 5
    $scorer->addGate($moatGate);     // Gate 1.5
    $scorer->addGate($runwayGate);   // Gate 2.75
    $scorer->addGate($complexityGate);// Gate 3.5

    $fullResult = $scorer->score($ticker, $dataProvider);
    echo "Quality Check: " . ($fullResult->passedQuality ? "PASSED ✅" : "FAILED ❌") . "\n";

    // Classification & Sizing
    echo "\n[Classification] Tier & Size:\n";
    $classifier = new \SixGates\Scoring\QualityTierClassifier($config['thresholds']['quality_tiers']);
    $sizer = new \SixGates\Scoring\PositionSizer($config['thresholds']['position_sizing']);

    $tier = $classifier->classify($fullResult);
    $size = $sizer->calculate($tier, $context ?? null);

    // Update Result
    $fullResult = $fullResult->withTierAndSize($tier, $size, $context ? ['phase' => $context->phase, 'risk' => $context->riskScore] : []);

    echo "Quality Tier: " . $tier . "\n";
    echo "Recommended Position: " . ($size * 100) . "%\n";

    // 5. Database Save
    try {
        $dbConnection = DatabaseFactory::create($config['database']['default']);

        // Ensure Stock Exists
        $stockRepo = new StockRepository($dbConnection);
        $stockRepo->upsert([
            'ticker' => $ticker,
            'company_name' => $ticker . ' (Auto)', // Placeholder name
            'is_active' => 1
        ]);

        // Save Result
        $analysisRepo = new \SixGates\Repositories\AnalysisResultRepository($dbConnection);
        $analysisRepo->save($fullResult);

        echo "\nAnalysis saved to database successfully. ✅\n";

    } catch (\Exception $e) {
        echo "\n\033[33mWARNING: Could not save to database: " . $e->getMessage() . "\033[0m\n";
    }

} catch (\Exception $e) {
    if ($e->getCode() === 402) {
        echo "\n\033[31mDATA ACCESS DENIED (402)\033[0m\n";
        echo "The FMP API returned a 'Payment Required' error.\n";
        echo "Reason: Your current subscription plan does not support this ticker or endpoint.\n";
        echo "Details: " . $e->getMessage() . "\n";
        exit(1);
    }

    echo "\n\033[31mANALYSIS FAILED\033[0m\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}

function dumpMetrics(array $metrics)
{
    foreach ($metrics as $k => $v) {
        echo " - $k: " . number_format((float) $v, 4) . "\n";
    }
}
