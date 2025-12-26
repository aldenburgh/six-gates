<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$signingSecret = $_ENV['SLACK_SIGNING_SECRET'];
$timestamp = time();
$body = 'token=MOCK_VERIFICATION_TOKEN&team_id=T0001&team_domain=example&channel_id=C2147483705&channel_name=test&user_id=U2147483697&user_name=Steve&command=/sixgates&text=analyze AAPL&response_url=https://hooks.slack.com/commands/1234/5678&trigger_id=13345224609.738474920.8088930838d88f008e0';

$baseString = "v0:$timestamp:$body";
$signature = 'v0=' . hash_hmac('sha256', $baseString, $signingSecret);

// Mock Headers and Body for the controller?
// We can't mock getallheaders() easily in CLI without runkit.
// Instead, we will instantiate the controller and call handle() directly, 
// mimicking what webhook.php does.

use SixGates\Services\Slack\SlackService;
use SixGates\Controllers\SlackController;
use GuzzleHttp\Client;

$client = new Client();
// We assume bot token is valid from env
$slackService = new SixGates\Services\Slack\SlackService($client, $_ENV['SLACK_BOT_TOKEN']);
$controller = new SlackController($slackService, $signingSecret);

$headers = [
    'x-slack-request-timestamp' => $timestamp,
    'x-slack-signature' => $signature
];

echo "Testing Webhook Controller Logic...\n";
$response = $controller->handle($headers, $body);

echo "Response:\n";
print_r($response);

if (isset($response['blocks']) && str_contains(json_encode($response), 'Starting analysis for AAPL')) {
    echo "\n✅ Webhook Test PASSED!\n";
} else {
    echo "\n❌ Webhook Test FAILED.\n";
}
