<?php

namespace App;

use App\Domain\NotFoundException;
use App\Http\Request;

class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $method = strtoupper($method);
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_-]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $trimmed = rtrim($regex, '/');
        if ($trimmed === '') {
            $trimmed = '/';
        }
        $regex = '#^' . $trimmed . '$#';
        $this->routes[$method][] = ['pattern' => $pattern, 'regex' => $regex, 'handler' => $handler];
    }

    public function dispatch(Request $request)
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = is_numeric($value) ? (int)$value : $value;
                    }
                }
                return call_user_func_array($route['handler'], array_merge([$request], $params));
            }
        }
        throw new NotFoundException('Route not found');
    }
}
