<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$client = new Client(['base_uri' => $_ENV['FMP_BASE_URL'] . '/']);
$provider = new FinancialModelingPrepProvider($client, $_ENV['FMP_API_KEY']);

echo "Fetching SPY History...\n";
try {
    echo "1. Checking Quote...\n";
    $quote = $provider->getQuote('SPY');
    dump($quote);

    // 2. V4 History Check
    echo "\n2. Checking V4 History...\n";
    $client = new Client();
    $res = $client->request('GET', "https://financialmodelingprep.com/api/v4/historical-price/AAPL?apikey=" . $_ENV['FMP_API_KEY']);
    echo "V4 Status: " . $res->getStatusCode() . "\n";
    echo "V4 Body: " . substr($res->getBody(), 0, 100) . "...\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

function dump($x)
{
    echo var_export($x, true) . "\n";
}
