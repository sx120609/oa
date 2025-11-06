<?php

use App\App;

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require $path;
        }
    });
}

$config = require __DIR__ . '/../config/app.php';
$app = new App($config);
$app->run();
