<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDO;
use PDOException;

final class ReservationController extends Controller
{
    public function update(): string
    {
        $actorId = $this->requireActor();

        $reservationId = $this->requirePositiveInt('reservation_id');
        $projectId = $this->requirePositiveInt('project_id');
        $deviceId = $this->requirePositiveInt('device_id');
        $reservedFrom = $this->timestampFromPost('from');
        $reservedTo = $this->timestampFromPost('to');

        if ($reservedFrom !== null && $reservedTo !== null && $reservedFrom >= $reservedTo) {
            throw new HttpException('时间范围不合法', 409);
        }

        $fromValue = date('Y-m-d H:i:s', $reservedFrom);
        $toValue = date('Y-m-d H:i:s', $reservedTo);

        $pdo = DB::connection();

        try {
            $pdo->beginTransaction();

            $conflict = $pdo->prepare(
                'SELECT 1 FROM reservations
                 WHERE device_id = :device_id
                   AND id != :id
                   AND reserved_from < :to_time
                   AND reserved_to > :from_time
                 LIMIT 1
                 FOR UPDATE'
            );
            $conflict->execute([
                ':device_id' => $deviceId,
                ':id' => $reservationId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
            ]);

            if ($conflict->fetchColumn()) {
                throw new HttpException('所选设备在该时间段已被预留或借用', 409);
            }

            $checkoutConflict = $pdo->prepare(
                'SELECT 1 FROM checkouts
                 WHERE device_id = :device_id
                   AND return_at IS NULL
                   AND checked_out_at < :to_time
                   AND due_at > :from_time
                 LIMIT 1
                 FOR UPDATE'
            );
            $checkoutConflict->execute([
                ':device_id' => $deviceId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
            ]);

            if ($checkoutConflict->fetchColumn()) {
                throw new HttpException('设备在该时间段存在借用冲突', 409);
            }

            $stmt = $pdo->prepare(
                'UPDATE reservations
                 SET project_id = :project_id,
                     device_id = :device_id,
                     reserved_from = :from_time,
                     reserved_to = :to_time
                 WHERE id = :id'
            );
            $stmt->execute([
                ':project_id' => $projectId,
                ':device_id' => $deviceId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
                ':id' => $reservationId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('预留记录不存在或无修改', 404);
            }

            $pdo->commit();

            AuditLogger::log($actorId, 'reservation', $reservationId, 'update', [
                'project_id' => $projectId,
                'device_id' => $deviceId,
                'reserved_from' => $fromValue,
                'reserved_to' => $toValue,
            ]);
        } catch (HttpException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new HttpException('更新预留失败', 500, $exception);
        }

        return Response::ok();
    }
}
