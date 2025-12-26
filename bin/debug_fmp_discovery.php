<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['FMP_API_KEY'];
$client = new Client();

echo "Testing FMP Endpoints...\n";

$endpoints = [
    'STABLE_QUERY_METRICS_PARAMS' => 'https://financialmodelingprep.com/stable/key-metrics?symbol=AAPL&period=annual&limit=5',
];

foreach ($endpoints as $name => $url) {
    echo "\nTrying $name...\n";
    try {
        $separator = (strpos($url, '?') !== false) ? '&' : '?';
        $fullUrl = $url . $separator . "apikey=$apiKey";
        $res = $client->request('GET', $fullUrl);
        echo "Status: " . $res->getStatusCode() . "\n";
        echo "Body: " . substr($res->getBody(), 0, 100) . "...\n";
    } catch (\Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}
