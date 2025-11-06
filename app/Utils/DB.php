<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use RuntimeException;

final class DB
{
    private static ?PDO $instance = null;

    /**
     * Retrieve a shared PDO connection configured from environment variables.
     */
    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = Env::get('DB_DSN');
        $user = Env::get('DB_USER') ?? '';
        $pass = Env::get('DB_PASS') ?? '';

        if (!$dsn) {
            throw new RuntimeException('DB_DSN is not configured.');
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('无法连接数据库。', 0, $e);
        }

        self::$instance = $pdo;

        return self::$instance;
    }
}
