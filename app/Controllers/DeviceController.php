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
    private const STATUSES = [
        'in_stock',
        'reserved',
        'checked_out',
        'transfer_pending',
        'lost',
        'repair',
    ];

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

    public function update(): string
    {
        $actorId = $this->requireActor();
        $deviceId = $this->requirePositiveInt('device_id');
        $model = $this->requireString('model');
        $status = strtolower($this->requireString('status'));
        $serial = $this->optionalString('serial');
        $photoUrl = $this->optionalString('photo_url');

        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException('设备状态不合法', 409);
        }

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare(
                'UPDATE devices SET model = :model, status = :status, serial = :serial, photo_url = :photo_url WHERE id = :id'
            );
            $stmt->execute([
                ':model' => $model,
                ':status' => $status,
                ':serial' => $serial,
                ':photo_url' => $photoUrl,
                ':id' => $deviceId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('设备不存在或无修改', 404);
            }

            AuditLogger::log($actorId, 'device', $deviceId, 'update', [
                'model' => $model,
                'status' => $status,
                'serial' => $serial,
                'photo_url' => $photoUrl,
            ]);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('更新设备失败', 500, $exception);
        }

        return Response::ok();
    }

    public function delete(): string
    {
        $actorId = $this->requireActor();
        if (!$this->actorIsAdmin()) {
            throw new HttpException('未登录或无权限', 403);
        }

        $deviceId = $this->requirePositiveInt('device_id');

        try {
            $pdo = DB::connection();
            $stmt = $pdo->prepare('DELETE FROM devices WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $deviceId]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('设备不存在', 404);
            }
        } catch (HttpException $exception) {
            throw $exception;
        } catch (PDOException $exception) {
            throw new HttpException('删除设备失败，可能存在关联记录', 500, $exception);
        }

        AuditLogger::log($actorId, 'device', $deviceId, 'delete');

        return Response::ok();
    }
}
