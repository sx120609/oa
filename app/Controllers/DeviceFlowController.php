<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\PenaltyService;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDO;
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

        $borrowerId = $this->requirePositiveInt('user_id');

        // TODO: Integrate penalty checks before allowing checkout (penalties table, rolling window).

        PenaltyService::ensureEligibleForCheckout($actorId);

        $checkedOutAtValue = date('Y-m-d H:i:s', $checkedOutAt);
        $dueAtValue = date('Y-m-d H:i:s', $dueAt);
        $note = $this->optionalString('note');
        $photo = $this->optionalString('photo');

        $pdo = DB::connection();

        try {
            $pdo->beginTransaction();

            $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $userCheck->execute([':id' => $borrowerId]);
            if (!$userCheck->fetchColumn()) {
                throw new HttpException('借出人不存在', 404);
            }

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
                ':user_id' => $borrowerId,
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
                'user_id' => $borrowerId,
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
        $actorId = $this->requireActor();

        $deviceId = $this->requirePositiveInt('device_id');
        $toUserId = $this->requirePositiveInt('to_user_id');
        $projectId = $this->optionalPositiveInt('project_id');
        $dueAtTs = $this->timestampFromPost('due_at', false);
        $dueAt = $dueAtTs ? date('Y-m-d H:i:s', $dueAtTs) : null;
        $note = $this->optionalString('note');

        $isAdminActor = $this->actorIsAdmin();
        $currentHolderId = null;

        $pdo = DB::connection();

        try {
            $pdo->beginTransaction();

            $existingPending = $pdo->prepare(
                'SELECT id FROM device_transfers WHERE device_id = :device_id AND status = "pending" LIMIT 1'
            );
            $existingPending->execute([':device_id' => $deviceId]);
            if ($existingPending->fetchColumn()) {
                throw new HttpException('存在待确认的转交请求', 409);
            }

            $checkoutStmt = $pdo->prepare(
                'SELECT * FROM checkouts WHERE device_id = :device_id AND return_at IS NULL ORDER BY checked_out_at DESC LIMIT 1 FOR UPDATE'
            );
            $checkoutStmt->execute([':device_id' => $deviceId]);
            $checkout = $checkoutStmt->fetch(PDO::FETCH_ASSOC);

            if (!$checkout) {
                throw new HttpException('当前设备未借出，无法发起转交', 409);
            }
            $currentHolderId = (int) $checkout['user_id'];

            if ($currentHolderId === $toUserId) {
                throw new HttpException('接收人与当前持有者不能相同', 409);
            }

            if ($currentHolderId !== $actorId && !$isAdminActor) {
                throw new HttpException('只有当前借用人可以发起转交', 409);
            }

            $insert = $pdo->prepare(
                'INSERT INTO device_transfers (device_id, from_checkout_id, from_user_id, to_user_id, target_project_id, target_due_at, transfer_type, status, note, requested_at)
                 VALUES (:device_id, :from_checkout_id, :from_user_id, :to_user_id, :target_project_id, :target_due_at, :transfer_type, "pending", :note, NOW())'
            );
            $insert->execute([
                ':device_id' => $deviceId,
                ':from_checkout_id' => $checkout['id'],
                ':from_user_id' => $currentHolderId,
                ':to_user_id' => $toUserId,
                ':target_project_id' => $projectId,
                ':target_due_at' => $dueAt,
                ':transfer_type' => 'checkout',
                ':note' => $note,
            ]);

            $updateDevice = $pdo->prepare('UPDATE devices SET status = :status WHERE id = :device_id');
            $updateDevice->execute([
                ':status' => 'transfer_pending',
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
            throw new HttpException('转交申请失败', 500, $exception);
        }

        AuditLogger::log($actorId, 'device', $deviceId, 'transfer_request', [
            'initiator' => $actorId,
            'current_holder' => $currentHolderId ?? null,
            'to_user' => $toUserId,
            'project' => $projectId,
            'due_at' => $dueAt,
            'note' => $note,
        ]);

        return Response::ok();
    }

    public function transferConfirm(): string
    {
        $actorId = $this->requireActor();
        $transferId = $this->requirePositiveInt('transfer_id');

        $projectIdOverride = $this->optionalPositiveInt('project_id');
        $dueAtOverrideTs = $this->timestampFromPost('due_at', false);
        $dueAtOverride = $dueAtOverrideTs ? date('Y-m-d H:i:s', $dueAtOverrideTs) : null;
        $note = $this->optionalString('note');

        $isAdminActor = $this->actorIsAdmin();

        $pdo = DB::connection();

        $transfer = null;
        $projectId = $projectIdOverride ?? null;
        $dueAt = $dueAtOverride;

        try {
            $pdo->beginTransaction();

            $transferStmt = $pdo->prepare(
                'SELECT * FROM device_transfers WHERE id = :id FOR UPDATE'
            );
            $transferStmt->execute([':id' => $transferId]);
            $transfer = $transferStmt->fetch(PDO::FETCH_ASSOC);

            if (!$transfer) {
                throw new HttpException('转交请求不存在', 404);
            }
            if ($transfer['status'] !== 'pending') {
                throw new HttpException('转交请求已处理', 409);
            }
            if ((int) $transfer['to_user_id'] !== $actorId && !$isAdminActor) {
                throw new HttpException('只有接收人或管理员可以确认转交', 403);
            }

            $checkoutStmt = $pdo->prepare(
                'SELECT * FROM checkouts WHERE id = :id FOR UPDATE'
            );
            $checkoutStmt->execute([':id' => $transfer['from_checkout_id']]);
            $checkout = $checkoutStmt->fetch(PDO::FETCH_ASSOC);
            if (!$checkout || $checkout['return_at'] !== null) {
                throw new HttpException('原借用记录不存在或已结束', 409);
            }

            $deviceStmt = $pdo->prepare('SELECT status FROM devices WHERE id = :id FOR UPDATE');
            $deviceStmt->execute([':id' => $transfer['device_id']]);
            $deviceRow = $deviceStmt->fetch(PDO::FETCH_ASSOC);
            if (!$deviceRow) {
                throw new HttpException('设备不存在', 404);
            }
            if ($deviceRow['status'] !== 'transfer_pending' && $deviceRow['status'] !== 'checked_out') {
                throw new HttpException('设备当前状态不支持转交', 409);
            }

            $projectId = $projectIdOverride ?? ($transfer['target_project_id'] ? (int) $transfer['target_project_id'] : null);
            if ($projectId === null && $checkout['project_id']) {
                $projectId = (int) $checkout['project_id'];
            }

            $dueAt = $dueAtOverride ?? ($transfer['target_due_at'] ?: $checkout['due_at']);
            if (!$dueAt) {
                throw new HttpException('缺少新的归还时间', 409);
            }

            $receiveAt = date('Y-m-d H:i:s');

            $closeCheckout = $pdo->prepare(
                'UPDATE checkouts SET return_at = :return_at WHERE id = :id'
            );
            $closeCheckout->execute([
                ':return_at' => $receiveAt,
                ':id' => $checkout['id'],
            ]);

            $newCheckout = $pdo->prepare(
                'INSERT INTO checkouts (project_id, device_id, user_id, checked_out_at, due_at, return_at, checkout_photo, note, created_at)
                 VALUES (:project_id, :device_id, :user_id, :checked_out_at, :due_at, NULL, NULL, :note, NOW())'
            );
            $newCheckout->execute([
                ':project_id' => $projectId,
                ':device_id' => $transfer['device_id'],
                ':user_id' => $transfer['to_user_id'],
                ':checked_out_at' => $receiveAt,
                ':due_at' => $dueAt,
                ':note' => $note ? "转交接收：{$note}" : null,
            ]);

            $updateTransfer = $pdo->prepare(
                'UPDATE device_transfers SET status = "accepted", confirmed_at = NOW(), target_project_id = :project_id, target_due_at = :due_at WHERE id = :id'
            );
            $updateTransfer->execute([
                ':project_id' => $projectId,
                ':due_at' => $dueAt,
                ':id' => $transfer['id'],
            ]);

            $updateDevice = $pdo->prepare('UPDATE devices SET status = :status WHERE id = :device_id');
            $updateDevice->execute([
                ':status' => 'checked_out',
                ':device_id' => $transfer['device_id'],
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
            throw new HttpException('确认转交失败', 500, $exception);
        }

        AuditLogger::log($actorId, 'device', (int) ($transfer['device_id'] ?? 0), 'transfer_confirm', [
            'from_user' => $transfer['from_user_id'] ?? null,
            'project' => $projectId,
            'due_at' => $dueAt,
        ]);

        return Response::ok();
    }

    public function updateCheckout(): string
    {
        $actorId = $this->requireActor();
        $checkoutId = $this->requirePositiveInt('checkout_id');
        $projectId = $this->optionalPositiveInt('project_id');
        $userId = $this->optionalPositiveInt('user_id');
        $dueAtTs = $this->timestampFromPost('due', false);

        if ($dueAtTs === null) {
            throw new HttpException('请提供新的归还时间', 409);
        }

        $dueAt = date('Y-m-d H:i:s', $dueAtTs);
        $note = $this->optionalString('note');

        $pdo = DB::connection();

        try {
            $pdo->beginTransaction();

            $checkoutStmt = $pdo->prepare('SELECT * FROM checkouts WHERE id = :id FOR UPDATE');
            $checkoutStmt->execute([':id' => $checkoutId]);
            $checkout = $checkoutStmt->fetch(PDO::FETCH_ASSOC);

            if (!$checkout) {
                throw new HttpException('借用记录不存在', 404);
            }

            $newProjectId = $projectId ?? ($checkout['project_id'] ? (int) $checkout['project_id'] : null);
            $newUserId = $userId ?? (int) $checkout['user_id'];

            if ($userId !== null) {
                $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
                $userCheck->execute([':id' => $newUserId]);
                if (!$userCheck->fetchColumn()) {
                    throw new HttpException('指定的借用人不存在', 404);
                }
            }

            $stmt = $pdo->prepare(
                'UPDATE checkouts SET project_id = :project_id, user_id = :user_id, due_at = :due_at, note = :note WHERE id = :id'
            );
            $stmt->execute([
                ':project_id' => $newProjectId,
                ':user_id' => $newUserId,
                ':due_at' => $dueAt,
                ':note' => $note ?: $checkout['note'],
                ':id' => $checkoutId,
            ]);

            if ($stmt->rowCount() === 0) {
                throw new HttpException('借用记录无变化', 404);
            }

            $pdo->commit();

            AuditLogger::log($actorId, 'checkout', $checkoutId, 'update', [
                'project_id' => $newProjectId,
                'user_id' => $newUserId,
                'due_at' => $dueAt,
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
            throw new HttpException('更新借用记录失败', 500, $exception);
        }

        return Response::ok();
    }
}
