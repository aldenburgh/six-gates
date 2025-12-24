<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\DataProviders\AnthropicProvider;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['ANTHROPIC_API_KEY'];
$model = $_ENV['ANTHROPIC_MODEL'];

echo "Testing Anthropic API...\n";
echo "Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Model: $model\n";

$client = new Client();
$provider = new AnthropicProvider($client, $apiKey, $model);

try {
    $response = $provider->generate("You are a helpful assistant.", "Say 'Hello World'");
    echo "Response: " . $response . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if ($e->getPrevious()) {
        echo "Previous: " . $e->getPrevious()->getMessage() . "\n";
    }
}
