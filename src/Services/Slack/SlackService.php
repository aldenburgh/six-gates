<?php

namespace SixGates\Services\Slack;

use GuzzleHttp\ClientInterface;

class SlackService
{
    private string $notificationChannel;
    private string $portfolioChannel;

    public function __construct(
        private ClientInterface $httpClient,
        private string $botToken,
        array $config = []
    ) {
        $this->notificationChannel = $config['notification_channel'] ?? '#six-gates-alerts';
        $this->portfolioChannel = $config['portfolio_channel'] ?? '#six-gates-portfolio';
    }

    public function postMessage(string $channel, array $blocks, string $urgency = 'info'): array
    {
        $emoji = match ($urgency) {
            'critical' => '🚨',
            'high' => '⚠️',
            'medium' => '📢',
            'low' => 'ℹ️',
            default => '📊'
        };

        // Prepend urgency to the first block if it's a section
        if ($urgency === 'critical' && isset($blocks[0]['type']) && $blocks[0]['type'] === 'section') {
            // Basic prepend logic or relies on caller to add header
        }

        try {
            $response = $this->httpClient->request('POST', 'https://slack.com/api/chat.postMessage', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->botToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'channel' => $channel,
                    'blocks' => $blocks,
                    'unfurl_links' => false,
                ]
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            // Log error in real app
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendBuyRecommendation(array $rec): void
    {
        // $rec should be the recommendation data structure
        // Simplified block generation for demo
        $blocks = [
            [
                'type' => 'header',
                'text' => ['type' => 'plain_text', 'text' => "📈 BUY RECOMMENDATION: {$rec['ticker']}"]
            ],
            [
                'type' => 'section',
                'text' => ['type' => 'mrkdwn', 'text' => "*{$rec['company_name']}*\nAction: BUY\nShares: {$rec['recommended_shares']} @ \${$rec['current_price']}"]
            ],
            [
                'type' => 'actions',
                'elements' => [
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => '✅ Approve'],
                        'style' => 'primary',
                        'action_id' => 'approve_recommendation',
                        'value' => $rec['id']
                    ],
                    [
                        'type' => 'button',
                        'text' => ['type' => 'plain_text', 'text' => '❌ Deny'],
                        'style' => 'danger',
                        'action_id' => 'deny_recommendation',
                        'value' => $rec['id']
                    ]
                ]
            ]
        ];

        $this->postMessage($this->notificationChannel, $blocks, 'high');
    }

    public function handleSlashCommand(array $payload): array
    {
        $command = $payload['command'] ?? '';
        $text = trim($payload['text'] ?? '');

        // Basic routing
        // /sixgates analyze AAPL
        // /sixgates portfolio
        // /sixgates alerts

        if (str_contains($command, 'sixgates')) {
            $parts = explode(' ', $text);
            $action = $parts[0] ?? 'help';
            $arg = $parts[1] ?? null;

            return match ($action) {
                'analyze' => $this->cmdAnalyze($arg),
                'portfolio' => $this->cmdPortfolio(),
                'alerts' => $this->cmdAlerts(),
                default => $this->cmdHelp(),
            };
        }

        return ['text' => "Unknown command: $command"];
    }

    private function cmdAnalyze(?string $ticker): array
    {
        if (!$ticker) {
            return ['text' => "⚠️ Usage: `/sixgates analyze [TICKER]`"];
        }

        // In a real async app, we'd queue a job.
        // For sync demo, we return a message saying we started.
        // Or if fast enough, do it? Analysis takes time.
        // Let's return immediate response.

        return [
            'response_type' => 'in_channel',
            'blocks' => [
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => "🔍 *Starting analysis for $ticker*..."]
                ],
                [
                    'type' => 'context',
                    'elements' => [['type' => 'mrkdwn', 'text' => "Use `/sixgates portfolio` to see results later."]]
                ]
            ]
        ];
    }

    private function cmdPortfolio(): array
    {
        // Mock Portfolio Logic
        return [
            'response_type' => 'ephemeral',
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => "📂 Current Portfolio"]
                ],
                [
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => "*AAPL*: 150 shares (+12%)\n*GOOGL*: 50 shares (-2%)\n*MSFT*: 80 shares (+5%)"]
                ]
            ]
        ];
    }

    private function cmdAlerts(): array
    {
        return [
            'response_type' => 'ephemeral',
            'text' => "✅ No active critical alerts."
        ];
    }

    private function cmdHelp(): array
    {
        return [
            'response_type' => 'ephemeral',
            'text' => "Available commands:\n`/sixgates analyze [TICKER]`\n`/sixgates portfolio`\n`/sixgates alerts`"
        ];
    }
}
