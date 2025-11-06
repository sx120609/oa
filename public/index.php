<?php

declare(strict_types=1);

use App\Utils\Env;
use App\Utils\HttpException;
use App\Utils\Response;
use App\Utils\Router;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/helpers.php';

ini_set('session.cookie_httponly', '1');

$secure = filter_var(env('SESSION_COOKIE_SECURE') ?? 'false', FILTER_VALIDATE_BOOLEAN);
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secure,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Env::load(dirname(__DIR__));

if (!defined('UPLOAD_ROOT')) {
    $uploadPath = env('UPLOAD_PATH');
    if ($uploadPath === null || trim($uploadPath) === '') {
        $uploadPath = dirname(__DIR__) . '/data/uploads';
    } elseif (!str_starts_with($uploadPath, DIRECTORY_SEPARATOR)) {
        $uploadPath = dirname(__DIR__) . '/' . ltrim($uploadPath, '/');
    }

    define('UPLOAD_ROOT', $uploadPath);
}

if (!is_dir(UPLOAD_ROOT) && !mkdir(UPLOAD_ROOT, 0775, true) && !is_dir(UPLOAD_ROOT)) {
    echo Response::error('上传目录不可用', 500);
    return;
}

$router = new Router();

$router->get('/', 'HomeController@index');
$router->post('/login', 'AuthController@login');
$router->post('/projects/create', 'ProjectController@create');
$router->post('/devices/create', 'DeviceController@create');
$router->post('/reservations/create', 'DeviceFlowController@reserve');
$router->post('/checkouts/create', 'DeviceFlowController@checkout');
$router->post('/returns/create', 'DeviceFlowController@return');
$router->post('/transfers/request', 'DeviceFlowController@transferRequest');
$router->post('/transfers/confirm', 'DeviceFlowController@transferConfirm');
$router->post('/extensions/request', 'ExtensionController@request');
$router->post('/extensions/approve', 'ExtensionController@approve');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST' && !verify_csrf_token($_POST['_token'] ?? null)) {
    echo Response::error('CSRF 校验失败', 419);
    return;
}

try {
    $response = $router->dispatch($method, $_SERVER['REQUEST_URI'] ?? '/');
} catch (HttpException $exception) {
    error_log(sprintf(
        '[%s] %s %s -> %d %s',
        date('c'),
        $method,
        $_SERVER['REQUEST_URI'] ?? '/',
        $exception->getStatusCode(),
        $exception->getMessage()
    ));
    echo Response::error($exception->getMessage(), $exception->getStatusCode());
    return;
} catch (\Throwable $exception) {
    error_log(sprintf(
        '[%s] %s %s -> 500 %s',
        date('c'),
        $method,
        $_SERVER['REQUEST_URI'] ?? '/',
        $exception->getMessage()
    ));
    echo Response::error('服务器内部错误', 500);
    return;
}

if ($response === null) {
    echo Response::error('资源不存在', 404);
    return;
}

if (is_string($response)) {
    echo $response;
    return;
}

if (is_array($response)) {
    header('Content-Type: application/json');
    try {
        echo json_encode($response, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        error_log($exception->getMessage());
        echo Response::error('响应数据编码失败', 500);
    }
    return;
}

echo Response::error('不支持的响应类型', 500);
