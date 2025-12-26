<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\Services\Slack\SlackService;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Verifying Slack Integration...\n";

$client = new Client();
$slack = new SlackService(
    $client,
    $_ENV['SLACK_BOT_TOKEN'],
    [
        'notification_channel' => $_ENV['SLACK_NOTIFICATION_CHANNEL']
    ]
);

echo "Sending Test Message...\n";
$response = $slack->postMessage(
    $_ENV['SLACK_NOTIFICATION_CHANNEL'],
    [
        [
            'type' => 'section',
            'text' => ['type' => 'mrkdwn', 'text' => "*Six Gates V7 Setup* testing Slack connectivity."]
        ]
    ]
);

echo "Response:\n";
var_dump($response);

if ($response['ok'] ?? false) {
    echo "\n✅ SLACK INTEGRATION SUCCESSFUL! Message sent.\n";
} else {
    echo "\n❌ SLACK INTEGRATION FAILED.\n";
    echo "Reason: " . ($response['error'] ?? 'Unknown') . "\n";
    if (($response['error'] ?? '') === 'missing_scope') {
        echo "Hint: Add 'chat:write' scope to your Bot Token in Slack App Settings.\n";
    }

    // Setup generic client for list check
    if (($response['error'] ?? '') === 'channel_not_found') {
        echo "\nDebugging Channel Visibility...\n";
        try {
            $listRes = $client->request('GET', 'https://slack.com/api/conversations.list', [
                'headers' => ['Authorization' => 'Bearer ' . $_ENV['SLACK_BOT_TOKEN']]
            ]);
            $listData = json_decode($listRes->getBody()->getContents(), true);

            if (!($listData['ok'] ?? false)) {
                echo "Could not list channels. Error: " . ($listData['error'] ?? 'Unknown') . "\n";
            } else {
                echo "Bot can see the following channels:\n";
                foreach ($listData['channels'] ?? [] as $ch) {
                    echo " - #" . $ch['name'] . " (ID: " . $ch['id'] . ")\n";
                }
                echo "If your target channel is not here, you MUST invite the bot to it.\n";
            }
        } catch (\Exception $e) {
            echo "List check failed: " . $e->getMessage() . "\n";
        }
    }
}
