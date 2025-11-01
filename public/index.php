<?php

use App\Handlers\Assets;
use App\Handlers\Repairs;
use App\Handlers\Reports;
use App\Http;
use App\Router;
use App\Util;

$app = require __DIR__ . '/../src/bootstrap.php';

$providedKey = Util::requestHeader('X-Api-Key') ?? '';
$expectedKey = $app['config']['api_key'] ?? 'devkey';

if (!hash_equals($expectedKey, $providedKey)) {
    Http::error('Unauthorized', 401, 'unauthorized');
    exit;
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$router->dispatch($method, $path);
