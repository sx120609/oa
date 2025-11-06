<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\PenaltyService;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDOException;

final class DeviceFlowController extends Controller
{
    public function reserve(): string
    {
        $actorId = $this->requireActor();

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

            $reservationConflict = $pdo->prepare(
                'SELECT 1
                 FROM reservations
                 WHERE device_id = :device_id
                   AND reserved_from < :to_time
                   AND reserved_to > :from_time
                 LIMIT 1
                 FOR UPDATE'
            );
            $reservationConflict->execute([
                ':device_id' => $deviceId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
            ]);

            if ($reservationConflict->fetchColumn()) {
                $pdo->rollBack();
                throw new HttpException('所选时间段设备不可用', 409);
            }

            $checkoutConflict = $pdo->prepare(
                'SELECT 1
                 FROM checkouts
                 WHERE device_id = :device_id
                   AND checked_out_at < :to_time
                   AND (return_at IS NULL OR return_at > :from_time)
                 LIMIT 1
                 FOR UPDATE'
            );
            $checkoutConflict->execute([
                ':device_id' => $deviceId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
            ]);

            if ($checkoutConflict->fetchColumn()) {
                $pdo->rollBack();
                throw new HttpException('所选时间段设备不可用', 409);
            }

            $insert = $pdo->prepare(
                'INSERT INTO reservations (project_id, device_id, reserved_from, reserved_to, created_by, created_at)
                 VALUES (:project_id, :device_id, :from_time, :to_time, :created_by, NOW())'
            );
            $insert->execute([
                ':project_id' => $projectId,
                ':device_id' => $deviceId,
                ':from_time' => $fromValue,
                ':to_time' => $toValue,
                ':created_by' => $actorId,
            ]);

            $reservationId = (int) $pdo->lastInsertId();

            $update = $pdo->prepare(
                'UPDATE devices SET status = :status WHERE id = :device_id'
            );
            $update->execute([
                ':status' => 'reserved',
                ':device_id' => $deviceId,
            ]);

            $pdo->commit();
        } catch (HttpException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new HttpException('设备预留失败', 500, $exception);
        }

        AuditLogger::log(
            $actorId,
            'reservation',
            $reservationId,
            'reserve',
            [
                'project_id' => $projectId,
                'device_id' => $deviceId,
                'from' => $fromValue,
                'to' => $toValue,
            ]
        );

        return Response::ok();
    }

    public function checkout(): string
    {
        $actorId = $this->requireActor();

        $projectId = $this->optionalPositiveInt('project_id');
        $deviceId = $this->requirePositiveInt('device_id');
        $checkedOutAt = $this->timestampFromPost('now');
        $dueAt = $this->timestampFromPost('due');

        if ($checkedOutAt !== null && $dueAt !== null && $checkedOutAt >= $dueAt) {
            throw new HttpException('时间范围不合法', 409);
        }

        // TODO: Integrate penalty checks before allowing checkout (penalties table, rolling window).

        PenaltyService::ensureEligibleForCheckout($actorId);

        $checkedOutAtValue = date('Y-m-d H:i:s', $checkedOutAt);
        $dueAtValue = date('Y-m-d H:i:s', $dueAt);
        $note = $this->optionalString('note');
        $photo = $this->optionalString('photo');

        $pdo = DB::connection();

        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare('SELECT status FROM devices WHERE id = :device_id FOR UPDATE');
            $lock->execute([':device_id' => $deviceId]);
            $currentStatus = $lock->fetchColumn();

            if ($currentStatus === false || !in_array($currentStatus, ['in_stock', 'reserved'], true)) {
                $pdo->rollBack();
                throw new HttpException('设备当前不可借出', 409);
            }

            $insert = $pdo->prepare(
                'INSERT INTO checkouts (project_id, device_id, user_id, checked_out_at, due_at, checkout_photo, note, created_at)
                 VALUES (:project_id, :device_id, :user_id, :checked_out_at, :due_at, :photo, :note, NOW())'
            );
            $insert->execute([
                ':project_id' => $projectId,
                ':device_id' => $deviceId,
                ':user_id' => $actorId,
                ':checked_out_at' => $checkedOutAtValue,
                ':due_at' => $dueAtValue,
                ':photo' => $photo,
                ':note' => $note,
            ]);

            $checkoutId = (int) $pdo->lastInsertId();

            $update = $pdo->prepare(
                'UPDATE devices SET status = :status WHERE id = :device_id'
            );
            $update->execute([
                ':status' => 'checked_out',
                ':device_id' => $deviceId,
            ]);

            $pdo->commit();
        } catch (HttpException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new HttpException('设备借出失败', 500, $exception);
        }

        AuditLogger::log(
            $actorId,
            'checkout',
            $checkoutId,
            'checkout',
            [
                'project_id' => $projectId,
                'device_id' => $deviceId,
                'checked_out_at' => $checkedOutAtValue,
                'due_at' => $dueAtValue,
                'note' => $note,
                'photo' => $photo,
            ]
        );

        return Response::ok();
    }

    public function return(): string
    {
        $actorId = $this->requireActor();

        $deviceId = $this->requirePositiveInt('device_id');
        $returnAt = $this->timestampFromPost('now');
        $photo = $this->optionalString('photo');
        $note = $this->optionalString('note');

        $returnAtValue = date('Y-m-d H:i:s', $returnAt);

        $pdo = DB::connection();
        try {
            $pdo->beginTransaction();

            $checkoutStmt = $pdo->prepare(
                'SELECT id, user_id, due_at
                 FROM checkouts
                 WHERE device_id = :device_id
                   AND return_at IS NULL
                 ORDER BY checked_out_at DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            $checkoutStmt->execute([':device_id' => $deviceId]);
            $checkout = $checkoutStmt->fetch(\PDO::FETCH_ASSOC);

            if ($checkout === false) {
                $pdo->rollBack();
                throw new HttpException('未找到待归还的借用记录', 409);
            }

            $checkoutId = (int) $checkout['id'];
            $borrowerId = (int) $checkout['user_id'];
            $dueAtValue = (string) $checkout['due_at'];
            $isOverdue = strtotime($dueAtValue) !== false && strtotime($dueAtValue) < $returnAt;

            $updateCheckout = $pdo->prepare(
                'UPDATE checkouts
                 SET return_at = :return_at, return_photo = :photo
                 WHERE id = :id'
            );
            $updateCheckout->execute([
                ':return_at' => $returnAtValue,
                ':photo' => $photo,
                ':id' => $checkoutId,
            ]);

            $updateDevice = $pdo->prepare(
                'UPDATE devices SET status = :status WHERE id = :device_id'
            );
            $updateDevice->execute([
                ':status' => 'in_stock',
                ':device_id' => $deviceId,
            ]);

            if ($isOverdue) {
                $body = sprintf(
                    '设备 %d 于 %s 归还，原定归还时间 %s，已超期。',
                    $deviceId,
                    $returnAtValue,
                    $dueAtValue
                );

                $notify = $pdo->prepare(
                    'INSERT INTO notifications (user_id, title, body, not_before, delivered_at, created_at)
                     VALUES (:user_id, :title, :body, NULL, NULL, NOW())'
                );
                $notify->execute([
                    ':user_id' => $borrowerId,
                    ':title' => '超期归还',
                    ':body' => $body,
                ]);
            }

            $pdo->commit();
        } catch (HttpException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new HttpException('设备归还失败', 500, $exception);
        }

        AuditLogger::log(
            $actorId,
            'checkout',
            $checkoutId,
            'return',
            [
                'device_id' => $deviceId,
                'return_at' => $returnAtValue,
                'photo' => $photo,
                'note' => $note,
            ]
        );

        return Response::ok();
    }

    public function transferRequest(): string
    {
        // TODO: Implement device transfer request flow (set status to transfer_pending and create transfer record).
        return Response::error('功能未实现', 501);
    }

    public function transferConfirm(): string
    {
        // TODO: Implement device transfer confirmation flow (revert status to checked_out and reset countdown policy).
        return Response::error('功能未实现', 501);
    }
}
