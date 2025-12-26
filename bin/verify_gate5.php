<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\Gates\NarrativeGate;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$apiKey = $_ENV['FMP_API_KEY'] ?? null;
if (!$apiKey) {
    die("FMP_API_KEY not found in .env\n");
}

$client = new Client(['base_uri' => 'https://financialmodelingprep.com/stable/']);
$provider = new FinancialModelingPrepProvider($client, $apiKey);

echo "\n--- Verifying NarrativeGate (Gate 5) for GOOG ---\n";

$gate = new NarrativeGate([]); // Passing empty thresholds
$result = $gate->analyze('GOOG', $provider);

echo "Gate ID: " . $result->gateName . "\n";
echo "Passed: " . ($result->passed ? 'Yes' : 'No') . "\n";
echo "Failure Reason: " . ($result->killReason ?? 'None') . "\n";

$metrics = $result->metrics;
echo "Metrics:\n";
print_r($metrics);

$metadata = $result->details;
echo "Metadata:\n";
print_r($metadata);

if ($result->killReason === 'Insufficient data') {
    echo "TEST FAILED: Still returning Insufficient data.\n";
    exit(1);
} else {
    echo "TEST PASSED: Data received.\n";
}
