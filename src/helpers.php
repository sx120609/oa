<?php

namespace App;

use Closure;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class Http
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE);
    }

    public static function error(string $message, int $status = 400, string $code = 'error', ?array $details = null): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        $payload = [
            'error' => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    public static function inputJson(): array
    {
        $input = file_get_contents('php://input');
        if ($input === false || $input === '') {
            return [];
        }

        $decoded = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            self::error('Invalid JSON payload', 400, 'invalid_json');
            exit;
        }

        return is_array($decoded) ? $decoded : [];
    }
}

class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        return $this;
    }

    public function dispatch(string $method, string $path): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                array_shift($matches);
                call_user_func_array($route['handler'], $matches);
                return;
            }
        }

        Http::error('Not Found', 404, 'not_found');
    }
}

class DB
{
    private static ?PDO $pdo = null;

    public static function connect(array $config): void
    {
        if (self::$pdo instanceof PDO) {
            return;
        }

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo instanceof PDO) {
            throw new RuntimeException('Database connection has not been initialised.');
        }

        return self::$pdo;
    }

    public static function tx(Closure $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            throw $throwable;
        }
    }
}

class Util
{
    public static function requestBody(): array
    {
        return Http::inputJson();
    }

    public static function requestHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
