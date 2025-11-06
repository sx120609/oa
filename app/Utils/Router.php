<?php

declare(strict_types=1);

namespace App\Utils;

use Closure;

final class Router
{
    /**
     * @var array<string, array<string, callable|string>>
     */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable|string $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    public function post(string $path, callable|string $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    /**
     * Dispatch the current request to a registered route.
     *
     * @return mixed|null
     */
    public function dispatch(string $method, string $uri)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath(parse_url($uri, PHP_URL_PATH) ?: '/');

        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            return null;
        }

        if (is_string($handler)) {
            return $this->invokeController($handler);
        }

        return $handler();
    }

    private function register(string $method, string $path, callable|string $handler): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        if (is_string($handler)) {
            $this->routes[$method][$path] = $handler;
            return;
        }

        $this->routes[$method][$path] = Closure::fromCallable($handler);
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? $path : rtrim($path, '/');
    }

    private function invokeController(string $handler)
    {
        $segments = explode('@', $handler, 2);
        if (count($segments) !== 2) {
            throw new HttpException('Invalid route handler definition', 500);
        }

        [$class, $method] = $segments;
        if (!str_contains($class, '\\')) {
            $class = 'App\\Controllers\\' . $class;
        }

        if (!class_exists($class)) {
            throw new HttpException(sprintf('Route handler class "%s" not found', $class), 500);
        }

        $controller = new $class();
        if (!is_callable([$controller, $method])) {
            throw new HttpException(sprintf('Route handler method "%s" not found on %s', $method, $class), 500);
        }

        return $controller->{$method}();
    }
}
