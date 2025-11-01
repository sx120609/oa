<?php

use App\Handlers\Assets;
use App\Handlers\Repairs;
use App\Handlers\Reports;
use App\Http;
use App\Router;
use App\Util;
use Throwable;

try {
    $app = require __DIR__ . '/../src/bootstrap.php';
} catch (Throwable $exception) {
    renderBootstrapFailure($exception);
    return;
}

$providedKey = Util::requestHeader('X-Api-Key') ?? '';
$expectedKey = $app['config']['api_key'] ?? 'devkey';

if (!hash_equals($expectedKey, $providedKey)) {
    Http::error('Unauthorized', 401, 'unauthorized');
    return;
}

$router = new Router();
$router->add('GET', '/^\/health$/', static function (): void {
    Http::json([
        'status' => 'ok',
        'timestamp' => Util::now(),
    ]);
});
$router->add('GET', '/^\/assets$/', [Assets::class, 'index']);
$router->add('POST', '/^\/assets$/', [Assets::class, 'store']);
$router->add('GET', '/^\/assets\/(\d+)$/', [Assets::class, 'show']);
$router->add('POST', '/^\/assets\/(\d+)\/assign$/', [Assets::class, 'assign']);
$router->add('POST', '/^\/assets\/(\d+)\/return$/', [Assets::class, 'release']);
$router->add('GET', '/^\/repairs$/', [Repairs::class, 'index']);
$router->add('POST', '/^\/repair-orders$/', [Repairs::class, 'store']);
$router->add('POST', '/^\/repair-orders\/(\d+)\/close$/', [Repairs::class, 'close']);
$router->add('GET', '/^\/reports\/summary$/', [Reports::class, 'summary']);
$router->add('GET', '/^\/reports\/costs$/', [Reports::class, 'costs']);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = resolvePath();

try {
    $router->dispatch($method, $path);
} catch (Throwable $exception) {
    renderUnhandledException($exception);
}

function resolvePath(): string
{
    $pathInfo = $_SERVER['PATH_INFO'] ?? null;
    if (is_string($pathInfo) && $pathInfo !== '') {
        return ensureLeadingSlash($pathInfo);
    }

    $requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($scriptName !== '' && str_starts_with($requestUri, $scriptName)) {
        $requestUri = substr($requestUri, strlen($scriptName)) ?: '/';
    } else {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir !== '' && str_starts_with($requestUri, $scriptDir)) {
            $requestUri = substr($requestUri, strlen($scriptDir)) ?: '/';
        }
    }

    return ensureLeadingSlash($requestUri === '' ? '/' : $requestUri);
}

function ensureLeadingSlash(string $path): string
{
    if ($path === '') {
        return '/';
    }

    return $path[0] === '/' ? $path : '/' . $path;
}

function renderBootstrapFailure(Throwable $exception): void
{
    renderJsonException($exception, 'Service initialisation failed', 'bootstrap_failed');
}

function renderUnhandledException(Throwable $exception): void
{
    renderJsonException($exception, 'Internal Server Error', 'internal_error');
}

function renderJsonException(Throwable $exception, string $message, string $code): void
{
    error_log((string) $exception);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');

    $payload = [
        'error' => $code,
        'message' => $message,
    ];

    if (shouldExposeDetails()) {
        $payload['details'] = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ];
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function shouldExposeDetails(): bool
{
    $debug = getenv('APP_DEBUG');
    if ($debug === false) {
        return false;
    }

    $normalized = strtolower(trim($debug));
    return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
}
