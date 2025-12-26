<?php

return [
    'fmp' => [
        'api_key' => $_ENV['FMP_API_KEY'] ?? '',
        'base_url' => $_ENV['FMP_BASE_URL'] ?? 'https://financialmodelingprep.com/stable',
    ],

    'anthropic' => [
        'api_key' => $_ENV['ANTHROPIC_API_KEY'] ?? '',
        'base_url' => 'https://api.anthropic.com/v1',
        'model' => $_ENV['ANTHROPIC_MODEL'] ?? 'claude-haiku-4-5-20251001',
        'max_tokens' => 2000,
        'timeout' => 30,
    ],

    'news' => [
        'api_key' => $_ENV['NEWS_API_KEY'] ?? '',
    ],
];
