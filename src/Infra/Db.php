<?php

namespace App\Infra;

use PDO;

class Db
{
    private static ?PDO $connection = null;
    private static array $config;

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = self::$config['dsn'] ?? null;
            $user = self::$config['user'] ?? null;
            $password = self::$config['password'] ?? null;
            $options = self::$config['options'] ?? [];
            self::$connection = new PDO($dsn, $user, $password, $options);
        }

        return self::$connection;
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::getConnection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
