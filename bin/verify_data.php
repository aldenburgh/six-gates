<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\DataProviders\CacheableProviderDecorator;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use SixGates\Gates\CapitalAllocationGate;

// Bootstrap
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$config = [
    'thresholds' => require __DIR__ . '/../config/thresholds.php',
    'api' => require __DIR__ . '/../config/api.php'
];

// Setup Provider
$httpClient = new Client([
    'base_uri' => $config['api']['fmp']['base_url'] . '/',
    'timeout' => 30.0
]);
$fmpProvider = new FinancialModelingPrepProvider($httpClient, $_ENV['FMP_API_KEY']);
$cache = new FilesystemAdapter('six_gates', 3600, __DIR__ . '/../storage/cache');
$dataProvider = new CacheableProviderDecorator($fmpProvider, $cache);

// Setup Gate
$gate = new CapitalAllocationGate($config['thresholds']['gate_1']);

// Analyze
$ticker = 'AAPL';
echo "Running analysis for $ticker...\n";

try {
    $result = $gate->analyze($ticker, $dataProvider);
    echo "Gate Result: " . ($result->passed ? "PASSED" : "FAILED") . "\n";
    echo "Metrics:\n";
    print_r($result->metrics);

    // Check for non-zero values
    if ($result->metrics['net_insider_shares_12m'] == 0 && $result->metrics['dividend_cuts'] == 0) {
        echo "\nWARNING: Both metrics are 0. This might be correct for AAPL, but check if data was fetched.\n";
        // Check if we can get raw data to verify
        $insider = $dataProvider->getInsiderTrading($ticker);
        echo "Raw Insider Data Count: " . count($insider) . "\n";

        $cashFlow = $dataProvider->getCashFlow($ticker, 5);
        echo "Raw Cash Flow Count: " . count($cashFlow) . "\n";
        if (count($cashFlow) > 0) {
            echo "Latest Dividend Paid: " . ($cashFlow[0]['dividendsPaid'] ?? 'N/A') . "\n";
        }
    } else {
        echo "\nSUCCESS: Metrics populated.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
