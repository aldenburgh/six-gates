<?php

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

// Hardcoded for debugging
$apiKey = '6LbGFAC3kxVvMvXw8JxTYTDJ39PQOCmK';
$baseUrl = 'https://financialmodelingprep.com/api/'; // Ending with api/

$client = new Client([
    'base_uri' => $baseUrl,
    'timeout' => 10.0,
    'verify' => false
]);

$endpoints = [
    'Quote (Basic)' => 'v3/quote/AAPL',
    'Key Metrics (Annual)' => 'v3/key-metrics/AAPL?period=annual',
    'Key Metrics TTM (v3)' => 'v3/key-metrics-ttm/AAPL',
    'Ratios TTM (v3)' => 'v3/ratios-ttm/AAPL',
    'Financials As Reported' => 'v3/financial-statement-full-as-reported/AAPL',
    'Score (v4)' => 'v4/score?symbol=AAPL',
    'Key Metrics Bulk (v4)' => 'v4/key-metrics-bulk?year=2023&period=annual',
    'Company Profile (v3)' => 'v3/profile/AAPL',
];

echo "Testing FMP API with Key: " . substr($apiKey, 0, 5) . "...\n";
echo "Base URL: $baseUrl\n\n";

foreach ($endpoints as $name => $path) {
    echo "Testing [$name]: $path ... ";
    try {
        // Append API Key manually
        $separator = str_contains($path, '?') ? '&' : '?';
        $fullPath = $path . $separator . 'apikey=' . $apiKey;

        $response = $client->get($fullPath);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        $json = json_decode($body, true);

        if ($statusCode === 200 && !empty($json)) {
            echo "OK " . (is_array($json) ? "(Count: " . count($json) . ")" : "") . "\n";
            // echo substr($body, 0, 100) . "...\n";
        } else {
            echo "Failed (Status: $statusCode, Body: " . substr($body, 0, 50) . ")\n";
        }
    } catch (ClientException $e) {
        echo "ERROR " . $e->getCode() . "\n";
        echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
    } catch (\Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
    echo "--------------------------------------------------\n";
}
