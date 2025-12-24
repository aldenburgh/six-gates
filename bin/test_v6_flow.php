<?php
// bin/test_v6_flow.php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;
use SixGates\Repositories\RecommendationRepository;
use SixGates\Repositories\ExecutionRepository;
use SixGates\Services\AnalysisService;
use SixGates\Services\RecommendationService;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\DataProviders\CacheableProviderDecorator;
use SixGates\Scoring\SixGatesScorer;
use SixGates\Scoring\QualityTierClassifier;
use SixGates\Scoring\PositionSizer;
use SixGates\MarketContext\MarketContextAssessor;
use SixGates\Enums\PortfolioType;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use GuzzleHttp\Client;

echo "V6 Flow Verification - Automated Test\n";
echo "-------------------------------------\n";

// 1. Setup
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$config = [
    'database' => require __DIR__ . '/../config/database.php',
    'api' => require __DIR__ . '/../config/api.php',
    'thresholds' => require __DIR__ . '/../config/thresholds.php',
];

$dbalConnection = DatabaseFactory::create($config['database']['default']);
$db = $dbalConnection->getNativeConnection();

// 2. Services
$recRepo = new RecommendationRepository($db);
$execRepo = new ExecutionRepository($db);

$httpClient = new Client(['base_uri' => $config['api']['fmp']['base_url'] . '/', 'timeout' => 10.0]);
// $fmpProvider = new FinancialModelingPrepProvider($httpClient, $config['api']['fmp']['api_key']);
// $cache = new FilesystemAdapter('six_gates', 3600, __DIR__ . '/../storage/cache');
// $dataProvider = new CacheableProviderDecorator($fmpProvider, $cache);
$dataProvider = new \SixGates\DataProviders\MockDataProvider();
echo "Using Mock Data Provider (FMP API blocked)\n";

$anthropicClient = new Client(['timeout' => 30.0]);
$anthropicProvider = new \SixGates\DataProviders\AnthropicProvider(
    $anthropicClient,
    $config['api']['anthropic']['api_key'] ?? '',
    $config['api']['anthropic']['model'] ?? 'claude-3-sonnet-20240229'
);

$scorer = new SixGatesScorer();
$classifier = new QualityTierClassifier($config['thresholds']['quality_tiers']);
$sizer = new PositionSizer($config['thresholds']['position_sizing']);
$marketInfo = new MarketContextAssessor($config['thresholds']['market']);

$analysisService = new AnalysisService(
    $dataProvider,
    $scorer,
    $classifier,
    $sizer,
    $marketInfo,
    $config,
    $anthropicProvider
);

$recService = new RecommendationService(
    $recRepo,
    new \SixGates\Domain\ShareCalculator(),
    new \SixGates\Domain\OrderTypeAdvisor()
);

// 3. Run Analysis
$ticker = 'AAPL';
echo "Analyzing $ticker...\n";

try {
    $result = $analysisService->analyze($ticker);
    echo "Result Generated. Tier: " . $result->qualityTier . "\n";

    // 4. Generate Recommendation
    $fairValue = 250.00; // Mock FV
    $price = 220.00; // Mock current

    echo "Creating Recommendation...\n";
    $dto = $recService->createBuyRecommendation(
        $ticker,
        "Apple Inc.",
        PortfolioType::GROWTH,
        10000.0,
        $price,
        $fairValue,
        $result->qualityTier,
        "Test Narrative",
        15.0 // VIX
    );

    echo "Recommendation Created:\n";
    echo " - Limit Price: {$dto->limitPrice}\n";
    echo " - Shares: {$dto->recommendedShares}\n";

    // 5. Verify DB Persistence
    $saved = $recRepo->getLatestForTicker($ticker);
    if ($saved && $saved->recommendedShares === $dto->recommendedShares) {
        echo "DB Verification: PASS ✅\n";
    } else {
        echo "DB Verification: FAIL ❌\n";
    }

} catch (\Exception $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
    exit(1);
}
