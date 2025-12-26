<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$config = require __DIR__ . '/../config/api.php';
$apiKey = $config['fmp']['api_key'];

$client = new Client(['base_uri' => 'https://financialmodelingprep.com/api/v3/']);
// Re-instantiate provider with a v3 base client to test if that's the issue, 
// OR use the one from the app which uses 'stable'
// The app uses: 'base_uri' => $config['api']['fmp']['base_url'] . '/', which is likely .../stable/

$appClient = new Client([
    'base_uri' => 'https://financialmodelingprep.com/stable/',
    'timeout' => 10
]);

$provider = new FinancialModelingPrepProvider($appClient, $apiKey);

echo "Fetching Profile for AAPL...\n";
try {
    $profile = $provider->getCompanyProfile('AAPL');
    if ($profile) {
        echo "Keys: " . implode(', ', array_keys($profile)) . "\n";
        echo "Price: " . ($profile['price'] ?? 'NULL') . "\n";
        echo "Market Cap: " . ($profile['mktCap'] ?? 'NULL') . "\n";
    } else {
        echo "Profile is null.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
