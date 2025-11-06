<?php

declare(strict_types=1);

use App\Utils\Env;
use App\Utils\HttpException;

/**
 * Access environment variables loaded via Env::load().
 */
function env(string $key, ?string $default = null): ?string
{
    return Env::get($key, $default);
}

/**
 * Escape values for safe HTML output.
 */
function escape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Render a view from app/Views.
 */
function view(string $template, array $data = []): string
{
    $viewPath = __DIR__ . '/Views/' . ltrim($template, '/');
    if (!str_ends_with($viewPath, '.php')) {
        $viewPath .= '.php';
    }

    if (!is_file($viewPath)) {
        throw new \RuntimeException(sprintf('View "%s" not found.', $template));
    }

    extract($data, EXTR_SKIP);

    ob_start();
    include $viewPath;

    return ob_get_clean() ?: '';
}

/**
 * Retrieve or generate the CSRF token for the active session.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new \RuntimeException('Session must be started before calling csrf_token().');
    }

    if (!isset($_SESSION['_csrf_token'])) {
        $key = env('APP_KEY') ?: bin2hex(random_bytes(16));
        $_SESSION['_csrf_token'] = hash_hmac('sha256', session_id() ?: random_bytes(8), $key);
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Render a hidden CSRF form field.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . escape(csrf_token()) . '">';
}

function verify_csrf_token(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $known = $_SESSION['_csrf_token'] ?? null;
    if ($token === null || $known === null) {
        return false;
    }

    return hash_equals($known, $token);
}

function require_csrf_token(?string $token): void
{
    if (!verify_csrf_token($token)) {
        throw new HttpException('Invalid CSRF token', 419);
    }
}
