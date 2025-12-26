<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use SixGates\Services\Slack\SlackService;
use SixGates\Controllers\SlackController;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configuration
$botToken = $_ENV['SLACK_BOT_TOKEN'];
$signingSecret = $_ENV['SLACK_SIGNING_SECRET'];

// Dependencies
$client = new Client();
$slackService = new SlackService($client, $botToken);
$controller = new SlackController($slackService, $signingSecret);

// Capture Request
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$body = file_get_contents('php://input');

// Handle
$response = $controller->handle($headers, $body);

// Output
header('Content-Type: application/json');
echo json_encode($response);
