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
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['content'][0]['text'] ?? '';

        } catch (GuzzleException $e) {
            throw new \RuntimeException("Anthropic API Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
