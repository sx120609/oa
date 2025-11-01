<?php

namespace App\Handlers;

use App\DB;
use App\Http;
use App\Util;
use PDO;

class Repairs
{
    public static function index(): void
    {
        Http::json([
            'message' => 'Repair order listing endpoint placeholder',
        ]);
    }

    public static function store(): void
    {
        $payload = Util::requestBody();
        $assetId = isset($payload['asset_id']) ? (int)$payload['asset_id'] : 0;
        $symptom = isset($payload['symptom']) ? trim((string)$payload['symptom']) : '';

        if ($assetId <= 0 || $symptom === '') {
            Http::error('asset_id and symptom are required', 422, 'validation_error');
            return;
        }

        $pdo = DB::pdo();
        $asset = self::findAsset($pdo, $assetId);
        if ($asset === null) {
            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        if ($asset['status'] !== 'in_use') {
            Http::error('Asset status must be in_use before repair', 409, 'invalid_status');
            return;
        }

        $now = Util::now();

        $result = DB::tx(function (PDO $pdo) use ($assetId, $symptom, $asset, $now) {
            $lockedAsset = self::lockAsset($pdo, $assetId);
            if ($lockedAsset === null) {
                return ['error' => 'not_found'];
            }

            if ($lockedAsset['updated_at'] !== $asset['updated_at']) {
                return ['error' => 'conflict'];
            }

            if ($lockedAsset['status'] !== 'in_use') {
                return ['error' => 'invalid_status'];
            }

            $insertOrder = $pdo->prepare('INSERT INTO repair_orders (asset_id, status, description, created_at, updated_at) VALUES (:asset_id, :status, :description, :created_at, :updated_at)');
            $insertOrder->execute([
                ':asset_id' => $assetId,
                ':status' => 'created',
                ':description' => $symptom,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $newOrderId = (int)$pdo->lastInsertId();

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id AND updated_at = :prev');
            $updateAsset->execute([
                ':status' => 'under_repair',
                ':updated_at' => $now,
                ':id' => $assetId,
                ':prev' => $lockedAsset['updated_at'],
            ]);

            if ($updateAsset->rowCount() === 0) {
                return ['error' => 'conflict'];
            }

            self::insertAssetLog(
                $pdo,
                $assetId,
                $lockedAsset['status'],
                'under_repair',
                'repair_create',
                (string)$newOrderId,
                $now
            );

            return [
                'order_id' => $newOrderId,
            ];
        });

        if (isset($result['error'])) {
            if ($result['error'] === 'conflict') {
                Http::error('Asset state has changed, please retry', 409, 'conflict');
                return;
            }

            if ($result['error'] === 'invalid_status') {
                Http::error('Asset status must be in_use before repair', 409, 'invalid_status');
                return;
            }

            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        $orderId = $result['order_id'];

        $order = self::findOrder($pdo, $orderId);

        Http::json([
            'order' => $order,
        ], 201);
    }

    public static function close(string $orderId): void
    {
        $id = (int)$orderId;
        if ($id <= 0) {
            Http::error('Repair order not found', 404, 'not_found');
            return;
        }

        $pdo = DB::pdo();
        $order = self::findOrder($pdo, $id);
        if ($order === null) {
            Http::error('Repair order not found', 404, 'not_found');
            return;
        }

        if (!in_array($order['status'], ['created', 'repairing', 'qa'], true)) {
            Http::error('Repair order cannot be closed from current status', 409, 'invalid_status');
            return;
        }

        $asset = self::findAsset($pdo, (int)$order['asset_id']);
        if ($asset === null) {
            Http::error('Asset not found', 404, 'not_found');
            return;
        }

        if ($asset['status'] !== 'under_repair') {
            Http::error('Asset is not under repair', 409, 'invalid_status');
            return;
        }

        $now = Util::now();

        $result = DB::tx(function (PDO $pdo) use ($id, $order, $asset, $now) {
            $lockedOrder = self::lockOrder($pdo, $id);
            if ($lockedOrder === null) {
                return ['error' => 'not_found'];
            }

            if ($lockedOrder['updated_at'] !== $order['updated_at']) {
                return ['error' => 'conflict'];
            }

            if (!in_array($lockedOrder['status'], ['created', 'repairing', 'qa'], true)) {
                return ['error' => 'invalid_status'];
            }

            $lockedAsset = self::lockAsset($pdo, (int)$asset['id']);
            if ($lockedAsset === null) {
                return ['error' => 'not_found'];
            }

            if ($lockedAsset['updated_at'] !== $asset['updated_at']) {
                return ['error' => 'conflict'];
            }

            if ($lockedAsset['status'] !== 'under_repair') {
                return ['error' => 'invalid_status'];
            }

            $updateOrder = $pdo->prepare('UPDATE repair_orders SET status = :status, updated_at = :updated_at WHERE id = :id AND updated_at = :prev');
            $updateOrder->execute([
                ':status' => 'closed',
                ':updated_at' => $now,
                ':id' => $id,
                ':prev' => $lockedOrder['updated_at'],
            ]);

            if ($updateOrder->rowCount() === 0) {
                return ['error' => 'conflict'];
            }

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id AND updated_at = :prev');
            $updateAsset->execute([
                ':status' => 'in_use',
                ':updated_at' => $now,
                ':id' => (int)$asset['id'],
                ':prev' => $lockedAsset['updated_at'],
            ]);

            if ($updateAsset->rowCount() === 0) {
                return ['error' => 'conflict'];
            }

            self::insertAssetLog(
                $pdo,
                (int)$asset['id'],
                $lockedAsset['status'],
                'in_use',
                'repair_close',
                (string)$id,
                $now
            );

            return ['ok' => true];
        });

        if (isset($result['error'])) {
            if ($result['error'] === 'conflict') {
                Http::error('Asset state has changed, please retry', 409, 'conflict');
                return;
            }

            if ($result['error'] === 'invalid_status') {
                Http::error('Repair order cannot be closed from current status', 409, 'invalid_status');
                return;
            }

            Http::error('Repair order not found', 404, 'not_found');
            return;
        }

        $order = self::findOrder($pdo, $id);

        Http::json([
            'order' => $order,
        ]);
    }

    private static function findAsset(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare('SELECT id, name, model, status, created_at, updated_at FROM assets WHERE id = :id');
        $statement->execute([':id' => $assetId]);
        $asset = $statement->fetch(PDO::FETCH_ASSOC);

        return $asset === false ? null : $asset;
    }

    private static function findOrder(PDO $pdo, int $orderId): ?array
    {
        $statement = $pdo->prepare('SELECT id, asset_id, status, description, created_at, updated_at FROM repair_orders WHERE id = :id');
        $statement->execute([':id' => $orderId]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);

        return $order === false ? null : $order;
    }

    private static function lockAsset(PDO $pdo, int $assetId): ?array
    {
        $statement = $pdo->prepare('SELECT id, name, model, status, created_at, updated_at FROM assets WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $assetId]);
        $asset = $statement->fetch(PDO::FETCH_ASSOC);

        return $asset === false ? null : $asset;
    }

    private static function lockOrder(PDO $pdo, int $orderId): ?array
    {
        $statement = $pdo->prepare('SELECT id, asset_id, status, description, created_at, updated_at FROM repair_orders WHERE id = :id FOR UPDATE');
        $statement->execute([':id' => $orderId]);
        $order = $statement->fetch(PDO::FETCH_ASSOC);

        return $order === false ? null : $order;
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
