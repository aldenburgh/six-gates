<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\FinancialModelingPrepProvider;
use SixGates\DataProviders\AnthropicProvider;
use SixGates\Services\Slack\SlackService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$client = new Client();

function testFMP()
{
    echo "1. Testing FinancialModelingPrep (FMP)...\n";
    // Ensure base_uri has trailing slash for Guzzle
    $base = rtrim($_ENV['FMP_BASE_URL'], '/') . '/';
    $client = new Client(['base_uri' => $base]);

    $provider = new FinancialModelingPrepProvider(
        $client,
        $_ENV['FMP_API_KEY']
    );
    try {
        $data = $provider->getQuote('SPY');
        if (!empty($data) && isset($data[0]['symbol'])) {
            echo "   ✅ SUCCESS: Fetched SPY quote: \${$data[0]['price']}\n";
        } else {
            echo "   ❌ FAILURE: Empty response.\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ FAILURE: " . $e->getMessage() . "\n";
    }
}

function testAnthropic($client)
{
    echo "\n2. Testing Anthropic (Claude)...\n";
    $key = $_ENV['ANTHROPIC_API_KEY'];
    if (str_contains($key, 'sk-ant')) {
        $provider = new AnthropicProvider($client, $key, $_ENV['ANTHROPIC_MODEL']);
        try {
            $reply = $provider->generate(
                "You are a test bot.",
                "Say 'Hello World'",
                10
            );
            echo "   ✅ SUCCESS: Response: '$reply'\n";
        } catch (\Exception $e) {
            echo "   ❌ FAILURE: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  SKIPPED: Invalid/Missing Key format.\n";
    }
}

function testSlack($client)
{
    echo "\n3. Testing Slack (Auth Check)...\n";
    $token = $_ENV['SLACK_BOT_TOKEN'];
    try {
        $res = $client->request('POST', 'https://slack.com/api/auth.test', [
            'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);
        $data = json_decode($res->getBody()->getContents(), true);

        if ($data['ok'] ?? false) {
            echo "   ✅ SUCCESS: Connected as {$data['user']} (Team: {$data['team']})\n";
        } else {
            echo "   ❌ FAILURE: " . ($data['error'] ?? 'Unknown') . "\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ FAILURE: " . $e->getMessage() . "\n";
    }
}

echo "🔍 INTEGRATION VERIFICATION SUITE 🔍\n";
echo "=====================================\n";

testFMP();
testAnthropic($client);
testSlack($client);

echo "\n=====================================\n";
