<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$apiKey = $_ENV['FMP_API_KEY'] ?? null;
if (!$apiKey) {
    die("FMP_API_KEY not found in .env\n");
}

$client = new Client(['base_uri' => 'https://financialmodelingprep.com/stable/']);
$provider = new FinancialModelingPrepProvider($client, $apiKey);
$ticker = 'GOOG';

echo "\n--- Debugging Company Profile for $ticker ---\n";

$profile = $provider->getCompanyProfile($ticker);
echo "Result from getCompanyProfile (current implementation):\n";
print_r($profile);

// Test new endpoint format directly
echo "\n--- Testing Corrected Endpoint Format ---\n";
$url = "https://financialmodelingprep.com/stable/profile?symbol=$ticker&apikey=$apiKey";
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Count: " . count($data) . "\n";
    if (count($data) > 0) {
        // print_r($data[0]);
        echo "Price: " . ($data[0]['price'] ?? 'MISSING') . "\n";
        echo "MktCap: " . ($data[0]['mktCap'] ?? $data[0]['marketCap'] ?? 'MISSING') . "\n";
    }
} catch (\Exception $e) {
    echo "Direct Test Failed: " . $e->getMessage() . "\n";
}


$price = $profile['price'] ?? 'MISSING';
$mktCap = $profile['mktCap'] ?? $profile['marketCap'] ?? 'MISSING';

echo "Extracted Price: $price\n";
echo "Extracted MktCap: $mktCap\n";

if ($price === 'MISSING' || $mktCap === 'MISSING') {
    echo "TEST FAILED: Price or Market Cap missing from profile.\n";
} else {
    echo "TEST PASSED: Data present.\n";
}
