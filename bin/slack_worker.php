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
use SixGates\Services\Slack\SlackService;

// 1. Bootstrap
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$config = [
    'database' => require __DIR__ . '/../config/database.php',
    'api' => require __DIR__ . '/../config/api.php',
    'thresholds' => require __DIR__ . '/../config/thresholds.php',
];

// 2. Parse Args
$ticker = $argv[1] ?? null;
$channelId = $argv[2] ?? null;
$userId = $argv[3] ?? null;

if (!$ticker || !$channelId) {
    die("Usage: php bin/slack_worker.php <TICKER> <CHANNEL_ID> [USER_ID]\n");
}

$httpClient = new Client([
    'base_uri' => $config['api']['fmp']['base_url'] . '/',
    'timeout' => 10.0
]);
$slackService = new SlackService($httpClient, $_ENV['SLACK_BOT_TOKEN']);

try {
    // Notify "Processing" (Optional, if we want to update the user again, but checking logs is enough)

    // 3. Setup Providers
    $fmpProvider = new FinancialModelingPrepProvider($httpClient, $config['api']['fmp']['api_key']);

    // Anthropic Provider for Moat & Report
    $anthropicProvider = new \SixGates\DataProviders\AnthropicProvider(
        $httpClient,
        $config['api']['anthropic']['api_key'],
        $config['api']['anthropic']['model']
    );

    // Cache
    $cache = new FilesystemAdapter('six_gates', 3600, __DIR__ . '/../storage/cache');
    $dataProvider = new CacheableProviderDecorator($fmpProvider, $cache);

    // 4. Setup Gates
    $economicGate = new EconomicEngineGate($config['thresholds']['gate_2']);
    $cashGate = new CashIntegrityGate($config['thresholds']['gate_3']);
    $capitalGate = new \SixGates\Gates\CapitalStructureGate($config['thresholds']['gate_2_5']);
    $valGate = new \SixGates\Gates\ValuationGate($config['thresholds']['gate_4']);
    $capAllocGate = new \SixGates\Gates\CapitalAllocationGate($config['thresholds']['gate_1']);
    $narrativeGate = new \SixGates\Gates\NarrativeGate($config['thresholds']['gate_5']);
    $runwayGate = new \SixGates\Gates\ReinvestmentRunwayGate($config['thresholds']['gate_2_75']);
    $complexityGate = new \SixGates\Gates\ComplexityFilterGate($config['thresholds']['gate_3_5']);

    $llmMoatAssessor = new \SixGates\Moat\LLMMoatAssessor($anthropicProvider, $dataProvider);
    $moatGate = new \SixGates\Gates\MoatAssessmentGate($config['thresholds']['gate_1_5'], $llmMoatAssessor);

    $marketContextAssessor = new \SixGates\MarketContext\MarketContextAssessor($config['thresholds']['market']);

    // 5. Run Analysis
    // Market Context
    try {
        $context = $marketContextAssessor->assess($dataProvider);
    } catch (\Exception $e) {
        $context = null;
    }

    // Builder/Scorer
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

    // Classifier & Sizer
    $classifier = new \SixGates\Scoring\QualityTierClassifier($config['thresholds']['quality_tiers']);
    $sizer = new \SixGates\Scoring\PositionSizer($config['thresholds']['position_sizing']);

    $tier = $classifier->classify($fullResult);
    $size = $sizer->calculate($tier, $context ?? null);

    $fullResult = $fullResult->withTierAndSize($tier, $size, $context ? ['phase' => $context->phase, 'risk' => $context->riskScore] : []);

    // 6. DB Save
    $dbConnection = DatabaseFactory::create($config['database']['default']);
    $stockRepo = new StockRepository($dbConnection);
    $stockRepo->upsert(['ticker' => $ticker, 'company_name' => $ticker . ' (Slack)', 'is_active' => 1]);

    $analysisRepo = new \SixGates\Repositories\AnalysisResultRepository($dbConnection);
    $analysisRepo->save($fullResult);

    // 7. Report Generation
    $reportRepo = new \SixGates\Repositories\ReportRepository($dbConnection);
    $reportService = new \SixGates\Services\ReportGeneratorService($anthropicProvider, $reportRepo);
    $report = $reportService->generateAndSave($fullResult);

    // 8. Slack Notification
    // Construct rich message
    $blocks = [];
    $blocks[] = [
        'type' => 'header',
        'text' => ['type' => 'plain_text', 'text' => "Analysis Report: $ticker"]
    ];

    $tierEmoji = match ($tier) {
        'Compounder' => '🌟',
        'Quality' => '✅',
        'Speculative' => '⚠️',
        'Uninvestable' => '⛔',
        default => '❓'
    };

    $blocks[] = [
        'type' => 'section',
        'fields' => [
            ['type' => 'mrkdwn', 'text' => "*Tier:*\n$tierEmoji $tier"],
            ['type' => 'mrkdwn', 'text' => "*Rec. Size:*\n" . ($size * 100) . "%"],
        ]
    ];

    $summary = $fullResult->passedQuality ? "Passed all quality gates." : "Failed one or more gates.";
    if (!$fullResult->passedQuality) {
        // Collect failure reasons
        $failures = [];
        foreach ($fullResult->gateResults as $gate => $res) {
            if (!$res->passed) {
                $failures[] = "• " . ucfirst(str_replace('_', ' ', $gate)) . ": " . $res->killReason;
            }
        }
        $summary = "*Failures:*\n" . implode("\n", $failures);
    }

    $blocks[] = [
        'type' => 'section',
        'text' => ['type' => 'mrkdwn', 'text' => $summary]
    ];

    $blocks[] = [
        'type' => 'divider'
    ];

    // Truncate report content if too long (Slack limit 3000 chars for text blocks usually)
    // We'll just show the recommendation and summary.
    // The report content from LLM usually has a "RECOMMENDATION:" line.

    $reportText = $report->reportContent;
    // Just send the whole thing in a code block or section?
    // Section is better but limited length.
    // Chunk report text if too long (Slack limit ~3000 chars per block)
    $reportText = $report->reportContent;
    $maxLen = 2800;

    // Split into chunks, preserving newlines where possible (simple chunking for now)
    $chunks = str_split($reportText, $maxLen);

    foreach ($chunks as $i => $chunk) {
        // Add header for continuation if not first chunk
        $text = ($i > 0 ? "...(continued)\n" : "") . $chunk;

        $blocks[] = [
            'type' => 'section',
            'text' => ['type' => 'mrkdwn', 'text' => $text]
        ];
    }

    if ($userId) {
        $blocks[] = [
            'type' => 'context',
            'elements' => [['type' => 'mrkdwn', 'text' => "Requested by <@$userId>"]]
        ];
    }

    $slackService->postMessage($channelId, $blocks, $fullResult->passedQuality ? 'medium' : 'low');

} catch (\Exception $e) {
    // Send Error to Slack
    $msg = [
        [
            'type' => 'section',
            'text' => ['type' => 'mrkdwn', 'text' => "❌ *Analysis Failed for $ticker*\nError: " . $e->getMessage()]
        ]
    ];
    $slackService->postMessage($channelId, $msg, 'critical');
    exit(1);
}
