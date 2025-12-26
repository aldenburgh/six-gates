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

$client = new Client(['base_uri' => 'https://financialmodelingprep.com/stable/']);

// 1. Inspect Cash Flow
echo "\n--- Cash Flow Inspection ---\n";
$url = "https://financialmodelingprep.com/stable/cash-flow-statement?symbol=AAPL&limit=5&period=annual&apikey=$apiKey";
echo "Testing: $url\n";
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Count: " . count($data) . "\n";
    if (count($data) > 0) {
        // echo "Keys: " . implode(", ", array_keys($data[0])) . "\n";
        foreach ($data as $i => $item) {
            echo "[$i] commonDividendsPaid: " . ($item['commonDividendsPaid'] ?? 'MISSING') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}

// 2. Test Insider Statistics
echo "\n--- Insider Statistics ---\n";
$url = "https://financialmodelingprep.com/stable/insider-trading/statistics?symbol=AAPL&apikey=$apiKey";
echo "Testing: $url\n";
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Count: " . count($data) . "\n";
    if (count($data) > 0) {
        $rec = $data[0];
        echo "Sample Record (Newest):\n";
        echo "Year/Q: " . $rec['year'] . " Q" . $rec['quarter'] . "\n";
        echo "totalAcquired: " . $rec['totalAcquired'] . "\n";
        echo "totalDisposed: " . $rec['totalDisposed'] . "\n";
    }
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}

// 3. Test Balance Sheet & Income Statement (Consistency)
echo "\n--- Financial Statements Consistency Test ---\n";

$endpoints = [
    'Balance Sheet' => "https://financialmodelingprep.com/stable/balance-sheet-statement?symbol=AAPL&limit=5&period=annual&apikey=$apiKey",
    'Income Statement' => "https://financialmodelingprep.com/stable/income-statement?symbol=AAPL&limit=5&period=annual&apikey=$apiKey"
];

foreach ($endpoints as $name => $u) {
    echo "$name: $u\n";
    try {
        $res = $client->get($u);
        $data = json_decode($res->getBody(), true);
        echo "Status: " . $res->getStatusCode() . "\n";
        echo "Count: " . count($data) . "\n";
        if (count($data) > 0) {
            echo "Sample Keys: " . implode(", ", array_slice(array_keys($data[0]), 0, 10)) . "...\n";
            if ($name === 'Income Statement') {
                echo "weightedAverageShsOut: " . ($data[0]['weightedAverageShsOut'] ?? 'MISSING') . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}

// 4. Test Earning Call Transcript
echo "\n--- Earning Call Transcript Test ---\n";
// Test specific quarter provided by user
$url = "https://financialmodelingprep.com/stable/earning-call-transcript?symbol=AAPL&year=2020&quarter=3&apikey=$apiKey";
echo "Testing: $url\n";
try {
    $res = $client->get($url);
    $data = json_decode($res->getBody(), true);
    echo "Count: " . count($data) . "\n";
    if (count($data) > 0) {
        $transcript = $data[0];
        echo "Quarter: " . ($transcript['quarter'] ?? 'N/A') . "\n";
        echo "Year: " . ($transcript['year'] ?? 'N/A') . "\n";
        echo "Content Length: " . strlen($transcript['content'] ?? '') . "\n";
        echo "Snippet: " . substr($transcript['content'] ?? '', 0, 100) . "...\n";
    }
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "Client Exception: " . $e->getMessage() . "\n";
    if ($e->getResponse()->getStatusCode() === 403) {
        echo "Graceful Failure: Subscription limited (403)\n";
    }
} catch (\Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
