<?php

namespace App\Http;

class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $headers;
    private ?string $rawBody;
    private ?array $jsonBody = null;
    private ?array $user = null;
    private array $attributes = [];

    public static function fromGlobals(): self
    {
        $instance = new self();
        $instance->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = explode('?', $uri, 2);
        $instance->path = rtrim($parts[0], '/') ?: '/';
        $instance->query = $_GET ?? [];
        $instance->headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $instance->rawBody = file_get_contents('php://input');

        return $instance;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQuery(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function getHeader(string $name, $default = null)
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === $normalized) {
                return $value;
            }
        }

        return $default;
    }

    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    public function setJsonBody(?array $data): void
    {
        $this->jsonBody = $data;
    }

    public function getJsonBody(): ?array
    {
        return $this->jsonBody;
    }

    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    public function getUser(): ?array
    {
        return $this->user;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
}
