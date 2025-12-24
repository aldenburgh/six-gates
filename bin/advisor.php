#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use SixGates\Database\DatabaseFactory;
use SixGates\Repositories\StockRepository;
use SixGates\Repositories\AnalysisResultRepository;
use SixGates\Repositories\RecommendationRepository;
use SixGates\Repositories\ExecutionRepository;
use SixGates\Repositories\PositionRepository;
use SixGates\Repositories\AuditLogRepository;
use SixGates\Services\AnalysisService;
use SixGates\Services\RecommendationService;
use SixGates\Services\AuditService;
use SixGates\Commands\AdvisorCommand;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\DataProviders\CacheableProviderDecorator;
use SixGates\Scoring\SixGatesScorer;
use SixGates\Scoring\QualityTierClassifier;
use SixGates\Scoring\PositionSizer;
use SixGates\MarketContext\MarketContextAssessor;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;
use GuzzleHttp\Client;

// 1. Environment
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// 2. Config
$config = [
    'database' => require __DIR__ . '/../config/database.php',
    'api' => require __DIR__ . '/../config/api.php',
    'thresholds' => require __DIR__ . '/../config/thresholds.php',
];

// 3. Database
try {
    $dbalConnection = DatabaseFactory::create($config['database']['default']);
    $db = $dbalConnection->getNativeConnection();
} catch (\Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Repositories
$recRepo = new RecommendationRepository($db);
$execRepo = new ExecutionRepository($db);
$posRepo = new PositionRepository($db);
$auditRepo = new AuditLogRepository($db);

// 5. External Services
$httpClient = new Client(['base_uri' => $config['api']['fmp']['base_url'] . '/', 'timeout' => 10.0]);
$fmpProvider = new FinancialModelingPrepProvider($httpClient, $config['api']['fmp']['api_key']);
$cache = new FilesystemAdapter('six_gates', 3600, __DIR__ . '/../storage/cache');
$dataProvider = new CacheableProviderDecorator($fmpProvider, $cache);

$anthropicProvider = new \SixGates\DataProviders\AnthropicProvider(
    $httpClient, // Reusing client, or new one? Guzzle client base_uri might conflict.
    $config['api']['anthropic']['api_key'] ?? '',
    $config['api']['anthropic']['model'] ?? 'claude-3-opus-20240229'
);
// Fix: New client for Anthropic to avoid FMP base_uri conflict
$anthropicClient = new Client(['timeout' => 30.0]);
$anthropicProvider = new \SixGates\DataProviders\AnthropicProvider(
    $anthropicClient,
    $config['api']['anthropic']['api_key'] ?? '',
    $config['api']['anthropic']['model'] ?? 'claude-3-sonnet-20240229'
);


// 6. Domain Services
$scorer = new SixGatesScorer();
$classifier = new QualityTierClassifier($config['thresholds']['quality_tiers']);
$sizer = new PositionSizer($config['thresholds']['position_sizing']);
$marketContextAssessor = new MarketContextAssessor($config['thresholds']['market']); // Fix: Pass config

$analysisService = new AnalysisService(
    $dataProvider, // Use decorated provider
    $scorer,
    $classifier,
    $sizer,
    $marketContextAssessor,
    $config,
    $anthropicProvider
);

$auditService = new AuditService($auditRepo);

$shareCalc = new \SixGates\Domain\ShareCalculator();
$orderAdvisor = new \SixGates\Domain\OrderTypeAdvisor();

$recService = new RecommendationService(
    $recRepo,
    $shareCalc,
    $orderAdvisor
);


// 7. CLI Application
$application = new Application();

$command = new AdvisorCommand(
    $analysisService,
    $recService,
    $recRepo,
    $execRepo
);

$application->add($command);
$application->setDefaultCommand($command->getName(), true);

$application->run();
