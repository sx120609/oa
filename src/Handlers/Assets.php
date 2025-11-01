<?php

namespace App\Handlers;

use App\DB;
use App\Http;
use App\Util;
use PDO;
use PDOException;

class Assets
{
    public static function index(): void
    {
        $pdo = DB::pdo();
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';

        $sql = 'SELECT id, name, model, status, created_at, updated_at FROM assets';
        $params = [];

        if ($status !== '') {
            $sql .= ' WHERE status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY id DESC';

        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $assets = $statement->fetchAll(PDO::FETCH_ASSOC);

        Http::json([
            'items' => $assets,
        ]);
    }

    public static function store(): void
    {
        $payload = Util::requestBody();
        $name = isset($payload['name']) ? trim((string)$payload['name']) : '';
        $model = isset($payload['model']) ? trim((string)$payload['model']) : null;

        if ($name === '') {
            Http::error('Asset name is required', 422, 'name_required');
            return;
        }

        $pdo = DB::pdo();
        $now = Util::now();

        $statement = $pdo->prepare('INSERT INTO assets (name, model, status, created_at, updated_at) VALUES (:name, :model, :status, :created_at, :updated_at)');
        $statement->execute([
            ':name' => $name,
            ':model' => $model !== null && $model !== '' ? $model : null,
            ':status' => 'in_stock',
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int)$pdo->lastInsertId();

        Http::json([
            'id' => $id,
        ], 201);
    }

    public static function show(string $assetId): void
    {
        Http::json([
            'message' => 'Asset detail endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }

    public static function assign(string $assetId): void
    {
        $id = (int)$assetId;
        $payload = Util::requestBody();
        $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
        $projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : 0;
        $requestNo = isset($payload['no']) ? trim((string)$payload['no']) : '';

        if ($userId <= 0 || $projectId <= 0 || $requestNo === '') {
            Http::error('Invalid assignment payload', 422, 'validation_error');
            return;
        }

        $pdo = DB::pdo();
        $asset = self::findAsset($pdo, $id);
        if ($asset === null) {
            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        if (!in_array($asset['status'], ['in_stock', 'in_use'], true)) {
            Http::error('Asset unavailable for assignment', 409, 'invalid_status');
            return;
        }

        $existing = self::findUsageByNo($pdo, $requestNo);
        if ($existing !== null) {
            if ((int)$existing['asset_id'] !== $id || $existing['type'] !== 'assign') {
                Http::error('Request number already used by another operation', 409, 'invalid_status');
                return;
            }

            Http::json([
                'asset' => $asset,
                'usage' => $existing,
                'idempotent' => true,
            ]);
            return;
        }

        $now = Util::now();

        try {
            $result = DB::tx(function (PDO $pdo) use ($id, $userId, $projectId, $requestNo, $asset, $now) {
                $lockedAsset = self::lockAsset($pdo, $id);
                if ($lockedAsset === null) {
                    return ['error' => 'not_found'];
                }

            if ($lockedAsset['updated_at'] !== $asset['updated_at']) {
                return ['error' => 'conflict'];
            }

            if (!in_array($lockedAsset['status'], ['in_stock', 'in_use'], true)) {
                return ['error' => 'invalid_status'];
            }

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id AND updated_at = :prev');
            $updateAsset->execute([
                ':status' => 'in_use',
                ':updated_at' => $now,
                ':id' => $id,
                ':prev' => $asset['updated_at'],
            ]);

            if ($updateAsset->rowCount() === 0) {
                return ['error' => 'conflict'];
            }

            $insertUsage = $pdo->prepare('INSERT INTO usages (asset_id, user_id, project_id, request_no, type, occurred_at) VALUES (:asset_id, :user_id, :project_id, :request_no, :type, :occurred_at)');
            $insertUsage->execute([
                ':asset_id' => $id,
                ':user_id' => $userId,
                ':project_id' => $projectId,
                ':request_no' => $requestNo,
                ':type' => 'assign',
                ':occurred_at' => $now,
            ]);

            self::insertAssetLog($pdo, $id, $lockedAsset['status'], 'in_use', 'assign', $requestNo, $now);

                return [
                    'usage_id' => (int)$pdo->lastInsertId(),
                ];
            });
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $usage = self::findUsageByNo($pdo, $requestNo);
                if ($usage !== null && (int)$usage['asset_id'] === $id && $usage['type'] === 'assign') {
                    $freshAsset = self::findAsset($pdo, $id);
                    Http::json([
                        'asset' => $freshAsset,
                        'usage' => $usage,
                        'idempotent' => true,
                    ]);
                    return;
                }

                Http::error('Request number already used by another operation', 409, 'invalid_status');
                return;
            }

            throw $exception;
        }

        if (isset($result['error'])) {
            if ($result['error'] === 'conflict') {
                Http::error('Asset state has changed, please retry', 409, 'conflict');
                return;
            }

            if ($result['error'] === 'invalid_status') {
                Http::error('Asset unavailable for assignment', 409, 'invalid_status');
                return;
            }

            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        $freshAsset = self::findAsset($pdo, $id);
        $usage = self::findUsageByNo($pdo, $requestNo);

        Http::json([
            'asset' => $freshAsset,
            'usage' => $usage,
            'idempotent' => false,
        ]);
    }

    public static function release(string $assetId): void
    {
        $id = (int)$assetId;
        $payload = Util::requestBody();
        $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : 0;
        $projectId = isset($payload['project_id']) ? (int)$payload['project_id'] : 0;
        $requestNo = isset($payload['no']) ? trim((string)$payload['no']) : '';

        if ($userId <= 0 || $projectId <= 0 || $requestNo === '') {
            Http::error('Invalid return payload', 422, 'validation_error');
            return;
        }

        $pdo = DB::pdo();
        $asset = self::findAsset($pdo, $id);
        if ($asset === null) {
            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        if ($asset['status'] !== 'in_use') {
            Http::error('Asset not in use', 409, 'invalid_status');
            return;
        }

        $existing = self::findUsageByNo($pdo, $requestNo);
        if ($existing !== null) {
            if ((int)$existing['asset_id'] !== $id || $existing['type'] !== 'return') {
                Http::error('Request number already used by another operation', 409, 'invalid_status');
                return;
            }

            Http::json([
                'asset' => $asset,
                'usage' => $existing,
                'idempotent' => true,
            ]);
            return;
        }

        $now = Util::now();

        try {
            $result = DB::tx(function (PDO $pdo) use ($id, $userId, $projectId, $requestNo, $asset, $now) {
                $lockedAsset = self::lockAsset($pdo, $id);
                if ($lockedAsset === null) {
                    return ['error' => 'not_found'];
                }

            if ($lockedAsset['updated_at'] !== $asset['updated_at']) {
                return ['error' => 'conflict'];
            }

            if ($lockedAsset['status'] !== 'in_use') {
                return ['error' => 'invalid_status'];
            }

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id AND updated_at = :prev');
            $updateAsset->execute([
                ':status' => 'in_stock',
                ':updated_at' => $now,
                ':id' => $id,
                ':prev' => $asset['updated_at'],
            ]);

            if ($updateAsset->rowCount() === 0) {
                return ['error' => 'conflict'];
            }

            $insertUsage = $pdo->prepare('INSERT INTO usages (asset_id, user_id, project_id, request_no, type, occurred_at) VALUES (:asset_id, :user_id, :project_id, :request_no, :type, :occurred_at)');
            $insertUsage->execute([
                ':asset_id' => $id,
                ':user_id' => $userId,
                ':project_id' => $projectId,
                ':request_no' => $requestNo,
                ':type' => 'return',
                ':occurred_at' => $now,
            ]);

            self::insertAssetLog($pdo, $id, $lockedAsset['status'], 'in_stock', 'return', $requestNo, $now);

                return [
                    'usage_id' => (int)$pdo->lastInsertId(),
                ];
            });
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $usage = self::findUsageByNo($pdo, $requestNo);
                if ($usage !== null && (int)$usage['asset_id'] === $id && $usage['type'] === 'return') {
                    $freshAsset = self::findAsset($pdo, $id);
                    Http::json([
                        'asset' => $freshAsset,
                        'usage' => $usage,
                        'idempotent' => true,
                    ]);
                    return;
                }

                Http::error('Request number already used by another operation', 409, 'invalid_status');
                return;
            }

            throw $exception;
        }

        if (isset($result['error'])) {
            if ($result['error'] === 'conflict') {
                Http::error('Asset state has changed, please retry', 409, 'conflict');
                return;
            }

            if ($result['error'] === 'invalid_status') {
                Http::error('Asset not in use', 409, 'invalid_status');
                return;
            }

            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        $freshAsset = self::findAsset($pdo, $id);
        $usage = self::findUsageByNo($pdo, $requestNo);

        Http::json([
            'asset' => $freshAsset,
            'usage' => $usage,
            'idempotent' => false,
        ]);
    }

    private static function findAsset(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare('SELECT id, name, model, status, created_at, updated_at FROM assets WHERE id = :id');
        $statement->execute([':id' => $assetId]);
        $asset = $statement->fetch(PDO::FETCH_ASSOC);

        return $asset === false ? null : $asset;
    }

    private static function lockAsset(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare('SELECT id, name, model, status, created_at, updated_at FROM assets WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $assetId]);
        $asset = $statement->fetch(PDO::FETCH_ASSOC);

        return $asset === false ? null : $asset;
    }

    private static function findUsageByNo(PDO $pdo, string $requestNo): ?array
    {
        $statement = $pdo->prepare('SELECT id, asset_id, user_id, project_id, request_no, type, occurred_at FROM usages WHERE request_no = :request_no');
        $statement->execute([':request_no' => $requestNo]);
        $usage = $statement->fetch(PDO::FETCH_ASSOC);

        return $usage === false ? null : $usage;
    }

    private static function insertAssetLog(PDO $pdo, int $assetId, ?string $fromStatus, string $toStatus, string $action, string $requestId, string $timestamp): void
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
}
