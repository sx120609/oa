<?php

namespace App;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(array $config): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = $config['driver'] ?? 'sqlite';

        try {
            if ($driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['mysql']['host'],
                    $config['mysql']['port'],
                    $config['mysql']['database'],
                    $config['mysql']['charset']
                );
                self::$pdo = new PDO($dsn, $config['mysql']['username'], $config['mysql']['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                $databaseFile = $config['sqlite']['database'];
                $directory = dirname($databaseFile);
                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }
                self::$pdo = new PDO('sqlite:' . $databaseFile, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false,
                ]);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (PDOException $exception) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => [
                    'message' => 'Database connection failed',
                    'details' => $exception->getMessage(),
                ],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return self::$pdo;
    }

    public static function initialize(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $queries = [
                'CREATE TABLE IF NOT EXISTS assets (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    serial_number VARCHAR(255) UNIQUE,
                    status VARCHAR(50) NOT NULL,
                    assigned_to VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
                'CREATE TABLE IF NOT EXISTS repair_orders (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    asset_id INT UNSIGNED NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    description TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL,
                    CONSTRAINT fk_repair_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
                'CREATE TABLE IF NOT EXISTS asset_logs (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    asset_id INT UNSIGNED NOT NULL,
                    from_status VARCHAR(50) NULL,
                    to_status VARCHAR(50) NOT NULL,
                    action VARCHAR(100) NOT NULL,
                    request_id VARCHAR(255) NULL,
                    created_at DATETIME NOT NULL,
                    CONSTRAINT fk_log_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
                'CREATE TABLE IF NOT EXISTS asset_assignments (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    asset_id INT UNSIGNED NOT NULL,
                    request_no VARCHAR(255) NOT NULL UNIQUE,
                    assigned_to VARCHAR(255) NOT NULL,
                    note TEXT NULL,
                    created_at DATETIME NOT NULL,
                    CONSTRAINT fk_assignment_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;',
            ];
        } else {
            $queries = [
                'CREATE TABLE IF NOT EXISTS assets (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    serial_number TEXT UNIQUE,
                    status TEXT NOT NULL,
                    assigned_to TEXT,
                    created_at TEXT NOT NULL
                );',
                'CREATE TABLE IF NOT EXISTS repair_orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    asset_id INTEGER NOT NULL,
                    status TEXT NOT NULL,
                    description TEXT,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL,
                    FOREIGN KEY(asset_id) REFERENCES assets(id) ON DELETE CASCADE
                );',
                'CREATE TABLE IF NOT EXISTS asset_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    asset_id INTEGER NOT NULL,
                    from_status TEXT,
                    to_status TEXT NOT NULL,
                    action TEXT NOT NULL,
                    request_id TEXT,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY(asset_id) REFERENCES assets(id) ON DELETE CASCADE
                );',
                'CREATE TABLE IF NOT EXISTS asset_assignments (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    asset_id INTEGER NOT NULL,
                    request_no TEXT NOT NULL UNIQUE,
                    assigned_to TEXT NOT NULL,
                    note TEXT,
                    created_at TEXT NOT NULL,
                    FOREIGN KEY(asset_id) REFERENCES assets(id) ON DELETE CASCADE
                );',
            ];
        }

        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }
    }
}
