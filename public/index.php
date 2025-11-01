<?php

use App\Database;
use App\HttpException;
use App\Response;

require_once __DIR__ . '/../app/Database.php';
require_once __DIR__ . '/../app/Response.php';
require_once __DIR__ . '/../app/HttpException.php';

$config = require __DIR__ . '/../config.php';

$pdo = Database::getConnection($config['db']);
Database::initialize($pdo);

authenticate($config['api_key'] ?? 'devkey');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$body = getJsonBody();

$routes = [
    ['GET', '#^/assets$#', 'listAssets'],
    ['POST', '#^/assets$#', 'createAsset'],
    ['GET', '#^/assets/(\\d+)$#', 'getAsset'],
    ['POST', '#^/assets/(\\d+)/assign$#', 'assignAsset'],
    ['POST', '#^/assets/(\\d+)/return$#', 'returnAsset'],
    ['POST', '#^/assets/(\\d+)/repairs$#', 'createRepairOrder'],
    ['GET', '#^/repair-orders$#', 'listRepairOrders'],
    ['PATCH', '#^/repair-orders/(\\d+)/status$#', 'updateRepairOrderStatus'],
    ['GET', '#^/assets/(\\d+)/logs$#', 'listAssetLogs'],
];

foreach ($routes as [$httpMethod, $pattern, $handler]) {
    if ($method !== $httpMethod) {
        continue;
    }

    if (preg_match($pattern, $path, $matches)) {
        array_shift($matches);
        try {
            $result = $handler($pdo, $matches, $body);
            $status = $result['status'] ?? 200;
            $data = $result['data'] ?? null;
            Response::success($data, $status);
        } catch (HttpException $exception) {
            Response::error($exception->getMessage(), $exception->getStatusCode(), $exception->getErrorCode(), $exception->getDetails());
        } catch (Throwable $throwable) {
            error_log($throwable);
            Response::error('Internal Server Error', 500);
        }
    }
}

Response::error('Not Found', 404);

function authenticate(string $expectedKey): void
{
    $receivedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!hash_equals($expectedKey, $receivedKey)) {
        Response::error('Unauthorized', 401, 'unauthorized');
    }
}

function getJsonBody(): array
{
    $input = file_get_contents('php://input');
    if ($input === false || $input === '') {
        return [];
    }

    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON payload', 400, 'invalid_json', [json_last_error_msg()]);
    }

    if (!is_array($decoded)) {
        return [];
    }

    return $decoded;
}

function listAssets(PDO $pdo, array $params, array $body): array
{
    $query = 'SELECT id, name, serial_number, status, assigned_to, created_at FROM assets';
    $conditions = [];
    $bindings = [];

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $conditions[] = 'status = :status';
        $bindings[':status'] = $_GET['status'];
    }

    if (!empty($conditions)) {
        $query .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $query .= ' ORDER BY id DESC';

    $statement = $pdo->prepare($query);
    $statement->execute($bindings);

    return ['data' => $statement->fetchAll()];
}

function createAsset(PDO $pdo, array $params, array $body): array
{
    $name = trim((string)($body['name'] ?? ''));
    $serialNumber = isset($body['serial_number']) ? trim((string)$body['serial_number']) : null;

    if ($name === '') {
        throw new HttpException(422, 'Asset name is required', 'validation_error', ['name' => 'required']);
    }

    $now = gmdate('Y-m-d H:i:s');

    $statement = $pdo->prepare('INSERT INTO assets (name, serial_number, status, assigned_to, created_at) VALUES (:name, :serial, :status, NULL, :created_at)');
    $statement->execute([
        ':name' => $name,
        ':serial' => $serialNumber ?: null,
        ':status' => 'in_stock',
        ':created_at' => $now,
    ]);

    $assetId = (int)$pdo->lastInsertId();

    $asset = getAssetById($pdo, $assetId);

    return ['data' => $asset, 'status' => 201];
}

function getAsset(PDO $pdo, array $params, array $body): array
{
    $assetId = (int)$params[0];
    $asset = getAssetById($pdo, $assetId);
    if (!$asset) {
        throw new HttpException(404, 'Asset not found', 'not_found');
    }

    return ['data' => $asset];
}

function assignAsset(PDO $pdo, array $params, array $body): array
{
    $assetId = (int)$params[0];
    $assignedTo = trim((string)($body['assigned_to'] ?? ''));
    $requestNo = trim((string)($body['no'] ?? ''));
    $note = isset($body['note']) ? trim((string)$body['note']) : null;

    if ($assignedTo === '') {
        throw new HttpException(422, 'assigned_to is required', 'validation_error', ['assigned_to' => 'required']);
    }

    if ($requestNo === '') {
        throw new HttpException(422, 'no is required for idempotent assignment', 'validation_error', ['no' => 'required']);
    }

    $asset = getAssetById($pdo, $assetId);
    if (!$asset) {
        throw new HttpException(404, 'Asset not found', 'not_found');
    }

    $existingAssignment = findAssignmentByNo($pdo, $requestNo);
    if ($existingAssignment) {
        if ((int)$existingAssignment['asset_id'] !== $assetId) {
            throw new HttpException(409, 'Request number already used by another asset', 'assignment_conflict');
        }

        $asset = getAssetById($pdo, $assetId);
        return ['data' => [
            'asset' => $asset,
            'assignment' => $existingAssignment,
            'idempotent' => true,
        ]];
    }

    if ($asset['status'] === 'under_repair') {
        throw new HttpException(409, 'Asset under repair cannot be assigned', 'invalid_state');
    }

    if ($asset['status'] !== 'in_stock') {
        throw new HttpException(409, 'Asset is not available for assignment', 'invalid_state');
    }

    $now = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $insertAssignment = $pdo->prepare('INSERT INTO asset_assignments (asset_id, request_no, assigned_to, note, created_at) VALUES (:asset_id, :request_no, :assigned_to, :note, :created_at)');
        $insertAssignment->execute([
            ':asset_id' => $assetId,
            ':request_no' => $requestNo,
            ':assigned_to' => $assignedTo,
            ':note' => $note,
            ':created_at' => $now,
        ]);

        $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, assigned_to = :assigned_to WHERE id = :id');
        $updateAsset->execute([
            ':status' => 'in_use',
            ':assigned_to' => $assignedTo,
            ':id' => $assetId,
        ]);

        logAssetStatus($pdo, $assetId, $asset['status'], 'in_use', 'assign', $requestNo, $now);

        $pdo->commit();
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }

    $assignment = findAssignmentByNo($pdo, $requestNo);
    $asset = getAssetById($pdo, $assetId);

    return ['data' => [
        'asset' => $asset,
        'assignment' => $assignment,
        'idempotent' => false,
    ]];
}

function returnAsset(PDO $pdo, array $params, array $body): array
{
    $assetId = (int)$params[0];
    $asset = getAssetById($pdo, $assetId);
    if (!$asset) {
        throw new HttpException(404, 'Asset not found', 'not_found');
    }

    if ($asset['status'] === 'under_repair') {
        throw new HttpException(409, 'Asset under repair cannot be returned directly', 'invalid_state');
    }

    $now = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, assigned_to = NULL WHERE id = :id');
        $updateAsset->execute([
            ':status' => 'in_stock',
            ':id' => $assetId,
        ]);

        logAssetStatus($pdo, $assetId, $asset['status'], 'in_stock', 'return', $body['request_id'] ?? null, $now);

        $pdo->commit();
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }

    $asset = getAssetById($pdo, $assetId);

    return ['data' => $asset];
}

function createRepairOrder(PDO $pdo, array $params, array $body): array
{
    $assetId = (int)$params[0];
    $description = isset($body['description']) ? trim((string)$body['description']) : null;

    $asset = getAssetById($pdo, $assetId);
    if (!$asset) {
        throw new HttpException(404, 'Asset not found', 'not_found');
    }

    $now = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $insertOrder = $pdo->prepare('INSERT INTO repair_orders (asset_id, status, description, created_at, updated_at) VALUES (:asset_id, :status, :description, :created_at, :updated_at)');
        $insertOrder->execute([
            ':asset_id' => $assetId,
            ':status' => 'created',
            ':description' => $description,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $orderId = (int)$pdo->lastInsertId();

        $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, assigned_to = NULL WHERE id = :id');
        $updateAsset->execute([
            ':status' => 'under_repair',
            ':id' => $assetId,
        ]);

        logAssetStatus($pdo, $assetId, $asset['status'], 'under_repair', 'send_for_repair', (string)$orderId, $now);

        $pdo->commit();
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }

    $order = getRepairOrderById($pdo, $orderId);

    return ['data' => $order, 'status' => 201];
}

function listRepairOrders(PDO $pdo, array $params, array $body): array
{
    $query = 'SELECT id, asset_id, status, description, created_at, updated_at FROM repair_orders ORDER BY id DESC';
    $statement = $pdo->query($query);

    return ['data' => $statement->fetchAll()];
}

function updateRepairOrderStatus(PDO $pdo, array $params, array $body): array
{
    $orderId = (int)$params[0];
    $status = isset($body['status']) ? trim((string)$body['status']) : '';

    $allowed = ['created', 'repairing', 'qa', 'closed'];
    if (!in_array($status, $allowed, true)) {
        throw new HttpException(422, 'Invalid repair order status', 'validation_error', ['status' => $allowed]);
    }

    $order = getRepairOrderById($pdo, $orderId);
    if (!$order) {
        throw new HttpException(404, 'Repair order not found', 'not_found');
    }

    $asset = getAssetById($pdo, (int)$order['asset_id']);
    if (!$asset) {
        throw new HttpException(404, 'Linked asset not found', 'not_found');
    }

    $now = gmdate('Y-m-d H:i:s');

    $pdo->beginTransaction();
    try {
        $updateOrder = $pdo->prepare('UPDATE repair_orders SET status = :status, updated_at = :updated_at WHERE id = :id');
        $updateOrder->execute([
            ':status' => $status,
            ':updated_at' => $now,
            ':id' => $orderId,
        ]);

        $fromStatus = $asset['status'];
        $toStatus = $asset['status'];
        if ($status === 'closed') {
            $toStatus = 'in_stock';
        } else {
            $toStatus = 'under_repair';
        }

        if ($asset['status'] !== $toStatus) {
            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status WHERE id = :id');
            $updateAsset->execute([
                ':status' => $toStatus,
                ':id' => $asset['id'],
            ]);

            logAssetStatus($pdo, (int)$asset['id'], $fromStatus, $toStatus, 'repair_status', (string)$orderId, $now);
        } else {
            logAssetStatus($pdo, (int)$asset['id'], $fromStatus, $toStatus, 'repair_status', (string)$orderId, $now);
        }

        $pdo->commit();
    } catch (Throwable $throwable) {
        $pdo->rollBack();
        throw $throwable;
    }

    $order = getRepairOrderById($pdo, $orderId);

    return ['data' => $order];
}

function listAssetLogs(PDO $pdo, array $params, array $body): array
{
    $assetId = (int)$params[0];
    $asset = getAssetById($pdo, $assetId);
    if (!$asset) {
        throw new HttpException(404, 'Asset not found', 'not_found');
    }

    $statement = $pdo->prepare('SELECT id, from_status, to_status, action, request_id, created_at FROM asset_logs WHERE asset_id = :asset_id ORDER BY id DESC');
    $statement->execute([':asset_id' => $assetId]);

    return ['data' => [
        'asset' => $asset,
        'logs' => $statement->fetchAll(),
    ]];
}

function getAssetById(PDO $pdo, int $assetId): ?array
{
    $statement = $pdo->prepare('SELECT id, name, serial_number, status, assigned_to, created_at FROM assets WHERE id = :id');
    $statement->execute([':id' => $assetId]);
    $asset = $statement->fetch();

    return $asset ?: null;
}

function getRepairOrderById(PDO $pdo, int $orderId): ?array
{
    $statement = $pdo->prepare('SELECT id, asset_id, status, description, created_at, updated_at FROM repair_orders WHERE id = :id');
    $statement->execute([':id' => $orderId]);
    $order = $statement->fetch();

    return $order ?: null;
}

function findAssignmentByNo(PDO $pdo, string $requestNo): ?array
{
    $statement = $pdo->prepare('SELECT id, asset_id, request_no, assigned_to, note, created_at FROM asset_assignments WHERE request_no = :no');
    $statement->execute([':no' => $requestNo]);
    $assignment = $statement->fetch();

    return $assignment ?: null;
}

function logAssetStatus(PDO $pdo, int $assetId, ?string $fromStatus, string $toStatus, string $action, ?string $requestId, string $timestamp): void
{
    $statement = $pdo->prepare('INSERT INTO asset_logs (asset_id, from_status, to_status, action, request_id, created_at) VALUES (:asset_id, :from_status, :to_status, :action, :request_id, :created_at)');
    $statement->execute([
        ':asset_id' => $assetId,
        ':from_status' => $fromStatus,
        ':to_status' => $toStatus,
        ':action' => $action,
        ':request_id' => $requestId,
        ':created_at' => $timestamp,
    ]);
}
