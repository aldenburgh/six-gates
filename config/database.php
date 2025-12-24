<?php

return [
    'default' => [
        'driver' => 'pdo_mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? 3306,
        'dbname' => $_ENV['DB_DATABASE'] ?? 'six_gates',
        'user' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
];
