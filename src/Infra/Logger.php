<?php

namespace App\Infra;

class Logger
{
    private static string $logDir = __DIR__ . '/../../storage';
    private static string $level = 'info';

    public static function configure(string $level = 'info'): void
    {
        self::$level = $level;
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0777, true);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('app.log', 'INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error.log', 'ERROR', $message, $context);
    }

    private static function write(string $file, string $level, string $message, array $context = []): void
    {
        $line = sprintf('[%s][%s] %s %s', date('c'), $level, $message, $context ? json_encode($context) : '');
        file_put_contents(self::$logDir . '/' . $file, $line . PHP_EOL, FILE_APPEND);
    }
}
