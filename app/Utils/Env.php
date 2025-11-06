<?php

declare(strict_types=1);

namespace App\Utils;

use Dotenv\Dotenv;

final class Env
{
    private static bool $loaded = false;

    /**
     * Load environment variables from the project root.
     */
    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        if (is_file($basePath . '/.env')) {
            Dotenv::createImmutable($basePath)->safeLoad();
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable with an optional default.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        if (!$key) {
            return $default;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }
}
