<?php

return [
    'api_key' => getenv('API_KEY') ?: 'devkey',
    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'sqlite',
        'sqlite' => [
            'database' => __DIR__ . '/../storage/database.sqlite',
        ],
        'mysql' => [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_DATABASE') ?: 'asset_lifecycle',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
        ],
    ],
];
