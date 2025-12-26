<?php

namespace SixGates\Controllers;

use SixGates\Services\Slack\SlackService;

class SlackController
{
    public function __construct(
        private SlackService $slackService,
        private string $signingSecret
    ) {
    }

    public function handle(array $headers, string $body): array
    {
        // 1. Verify Signature
        if (!$this->verifySignature($headers, $body)) {
            http_response_code(401);
            return ['text' => 'Invalid Request Signature'];
        }

        // 2. Parse Body (Slack sends form-urlencoded for commands usually, or JSON for events)
        // Commands are POST form fields. Events are JSON.
        // Let's assume Slash Command for now (application/x-www-form-urlencoded).

        $payload = [];
        parse_str($body, $payload);

        // If it's JSON (Event API), payload might be empty from parse_str
        if (empty($payload)) {
            $payload = json_decode($body, true) ?? [];
        }

        // 3. Dispatch
        // If it sends 'command', it's a slash command.
        if (isset($payload['command'])) {
            return $this->slackService->handleSlashCommand($payload);
        }

        // If 'type' is url_verification (Event API)
        if (($payload['type'] ?? '') === 'url_verification') {
            return ['challenge' => $payload['challenge']];
        }

        return ['text' => "Unhandled request type."];
    }

    private function verifySignature(array $headers, string $body): bool
    {
        $timestamp = $headers['x-slack-request-timestamp'] ?? null;
        $signature = $headers['x-slack-signature'] ?? null;

        if (!$timestamp || !$signature) {
            return false;
        }

        // Prevent replay attacks (5 mins)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $baseString = "v0:$timestamp:$body";
        $hash = 'v0=' . hash_hmac('sha256', $baseString, $this->signingSecret);

        return hash_equals($hash, $signature);
    }
}
