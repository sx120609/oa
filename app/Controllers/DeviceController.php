<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDOException;

final class DeviceController extends Controller
{
    public function create(): string
    {
        $actorId = $this->requireActor();

        $code = $this->requireString('code');
        $model = $this->requireString('model');
        $serial = $this->optionalString('serial');
        $photoUrl = $this->optionalString('photo_url');

        $status = 'in_stock';

        try {
            $pdo = DB::connection();

            $existsStmt = $pdo->prepare('SELECT id FROM devices WHERE code = :code LIMIT 1');
            $existsStmt->execute([':code' => $code]);
            if ($existsStmt->fetchColumn()) {
                throw new HttpException('设备编号已存在', 409);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO devices (code, model, serial, status, photo_url, created_at)
                 VALUES (:code, :model, :serial, :status, :photo_url, NOW())'
            );

            $stmt->execute([
                ':code' => $code,
                ':model' => $model,
                ':serial' => $serial,
                ':status' => $status,
                ':photo_url' => $photoUrl,
            ]);

            $deviceId = (int) $pdo->lastInsertId();
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('设备创建失败', 500, $exception);
        }

        AuditLogger::log(
            $actorId,
            'device',
            $deviceId,
            'create',
            [
                'code' => $code,
                'model' => $model,
                'serial' => $serial,
                'status' => $status,
                'photo_url' => $photoUrl,
                'created_by' => $actorId,
            ]
        );

        return Response::ok();
    }
}
