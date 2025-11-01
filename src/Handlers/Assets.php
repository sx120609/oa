<?php

namespace App\Handlers;

use App\DB;
use App\Http;
use App\Util;
use PDO;

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
        Http::json([
            'message' => 'Asset assignment endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }

    public static function release(string $assetId): void
    {
        Http::json([
            'message' => 'Asset return endpoint placeholder',
            'asset_id' => $assetId,
        ]);
    }
}
