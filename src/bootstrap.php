<?php

use App\DB;

require_once __DIR__ . '/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (str_starts_with($class, $prefix)) {
        $relative = substr($class, strlen($prefix));
        $path = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    }
});

$config = require __DIR__ . '/config.php';

DB::connect($config['database']);

return [
    'config' => $config,
];
