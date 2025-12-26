<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$apiKey = $_ENV['FMP_API_KEY'] ?? null;
if (!$apiKey) {
    die("FMP_API_KEY not found in .env\n");
}

$client = new Client();
$ticker = 'GOOG';

echo "\n--- Testing Gate 5 Data for $ticker ---\n";

// 1. Analyst Estimates
$url = "https://financialmodelingprep.com/stable/analyst-estimates?symbol=$ticker&period=annual&limit=10&apikey=$apiKey";
echo "Testing Analyst Estimates: $url\n";
try {
    $res = $client->get($url); //, ['http_errors' => false]);
    echo "Status: " . $res->getStatusCode() . "\n";
    $body = $res->getBody();
    $data = json_decode($body, true);
    echo "Count: " . count($data) . "\n";
    if (count($data) > 0) {
        echo "Sample Estimate (Latest):\n";
        print_r($data[0]);
    } else {
        echo "Data is EMPTY.\n";
    }
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }
}

// 2. Stock News (for context)
echo "\n--- Testing Stock News for $ticker ---\n";
$url = "https://financialmodelingprep.com/stable/news/stock?symbols=$ticker&limit=10&apikey=$apiKey";
echo "Testing News: $url\n";
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Count: " . count($data) . "\n";
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
