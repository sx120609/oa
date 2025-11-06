<?php

return [
    'app' => [
        'key' => getenv('APP_KEY') ?: 'demo-key',
        'log_level' => getenv('LOG_LEVEL') ?: 'info',
    ],
    'db' => [
        'dsn' => getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../storage/demo.sqlite',
        'user' => getenv('DB_USER') ?: null,
        'password' => getenv('DB_PASS') ?: null,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
];
