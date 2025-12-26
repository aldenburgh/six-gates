<?php

namespace SixGates\DataProviders;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class AnthropicProvider
{
    public function __construct(
        private ClientInterface $client,
        private string $apiKey,
        private string $model = 'claude-haiku-4-5-20251001',
        private string $apiVersion = '2023-06-01'
    ) {
    }

    public function generate(string $systemPrompt, string $userPrompt, int $maxTokens = 2000): string
    {
        $attempts = 0;
        $maxAttempts = 3;
        $lastException = null;

        while ($attempts < $maxAttempts) {
            $attempts++;
            try {
                $response = $this->client->request('POST', 'https://api.anthropic.com/v1/messages', [
                    'headers' => [
                        'x-api-key' => $this->apiKey,
                        'anthropic-version' => $this->apiVersion,
                        'content-type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $this->model,
                        'max_tokens' => $maxTokens,
                        'system' => $systemPrompt,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $userPrompt
                            ]
                        ]
                    ],
                    'timeout' => 60, // Increased timeout to 60 seconds
                    'connect_timeout' => 10,
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                return $data['content'][0]['text'] ?? '';

            } catch (GuzzleException $e) {
                $lastException = $e;
                // If it's a client error (4xx) other than 429 (Too Many Requests), don't retry
                if ($e->getCode() >= 400 && $e->getCode() < 500 && $e->getCode() !== 429) {
                    throw new \RuntimeException("Anthropic API Client Error: " . $e->getMessage(), $e->getCode(), $e);
                }

                // Wait briefly before retrying (exponential backoff could be better but simple sleep is fine for now)
                if ($attempts < $maxAttempts) {
                    sleep(1 * $attempts);
                }
            }
        }

        throw new \RuntimeException("Anthropic API Error after $maxAttempts attempts: " . $lastException->getMessage(), $lastException->getCode(), $lastException);
    }
}
