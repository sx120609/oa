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

        $orderId = DB::tx(function (PDO $pdo) use ($assetId, $symptom, $asset, $now): int {
            $insertOrder = $pdo->prepare('INSERT INTO repair_orders (asset_id, status, description, created_at, updated_at) VALUES (:asset_id, :status, :description, :created_at, :updated_at)');
            $insertOrder->execute([
                ':asset_id' => $assetId,
                ':status' => 'created',
                ':description' => $symptom,
                ':created_at' => $now,
                ':updated_at' => $now,
            ]);

            $newOrderId = (int)$pdo->lastInsertId();

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id');
            $updateAsset->execute([
                ':status' => 'under_repair',
                ':updated_at' => $now,
                ':id' => $assetId,
            ]);

            self::insertAssetLog(
                $pdo,
                $assetId,
                $asset['status'],
                'under_repair',
                'repair_create',
                (string)$newOrderId,
                $now
            );

            return $newOrderId;
        });

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

        DB::tx(function (PDO $pdo) use ($id, $asset, $now): void {
            $updateOrder = $pdo->prepare('UPDATE repair_orders SET status = :status, updated_at = :updated_at WHERE id = :id');
            $updateOrder->execute([
                ':status' => 'closed',
                ':updated_at' => $now,
                ':id' => $id,
            ]);

            $updateAsset = $pdo->prepare('UPDATE assets SET status = :status, updated_at = :updated_at WHERE id = :id');
            $updateAsset->execute([
                ':status' => 'in_use',
                ':updated_at' => $now,
                ':id' => (int)$asset['id'],
            ]);

            self::insertAssetLog(
                $pdo,
                (int)$asset['id'],
                $asset['status'],
                'in_use',
                'repair_close',
                (string)$id,
                $now
            );
        });

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
