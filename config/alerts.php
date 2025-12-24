<?php

return [
    'email' => [
        'enabled' => filter_var($_ENV['ALERT_EMAIL_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'to' => $_ENV['ALERT_EMAIL_TO'] ?? '',
    ],

    'telegram' => [
        'enabled' => filter_var($_ENV['ALERT_TELEGRAM_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'bot_token' => $_ENV['ALERT_TELEGRAM_BOT_TOKEN'] ?? '',
        'chat_id' => $_ENV['ALERT_TELEGRAM_CHAT_ID'] ?? '',
    ],
];
