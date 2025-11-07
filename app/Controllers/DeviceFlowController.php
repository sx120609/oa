<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuditLogger;
use App\Services\DeviceStatusService;
use App\Services\PenaltyService;
use App\Utils\DB;
use App\Utils\HttpException;
use App\Utils\Response;
use PDO;
use PDOException;

final class DeviceFlowController extends Controller
{
    private static bool $transferTableEnsured = false;

    private function ensureTransferTable(PDO $pdo): void
    {
        if (self::$transferTableEnsured) {
            return;
        }

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS device_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    device_id BIGINT UNSIGNED NOT NULL,
    from_checkout_id BIGINT UNSIGNED NOT NULL,
    from_user_id BIGINT UNSIGNED NOT NULL,
    to_user_id BIGINT UNSIGNED NOT NULL,
    target_project_id BIGINT UNSIGNED NULL,
    target_due_at DATETIME NULL,
    transfer_type ENUM('checkout', 'reservation') NOT NULL DEFAULT 'checkout',
    status ENUM('pending', 'accepted', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    note TEXT NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    INDEX idx_transfers_device_status (device_id, status),
    INDEX idx_transfers_to_user (to_user_id, status),
    CONSTRAINT fk_device_transfers_device FOREIGN KEY (device_id) REFERENCES devices (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_device_transfers_from_checkout FOREIGN KEY (from_checkout_id) REFERENCES checkouts (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_device_transfers_from_user FOREIGN KEY (from_user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_device_transfers_to_user FOREIGN KEY (to_user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_device_transfers_target_project FOREIGN KEY (target_project_id) REFERENCES projects (id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $pdo->exec($sql);
        self::$transferTableEnsured = true;
    }

    public function reserve(): string
    {
        $actorId = $this->requireActor();

        $projectId = $this->requirePositiveInt('project_id');
        $deviceId = $this->requirePositiveInt('device_id');
        $fromInput = $_POST['from'] ?? null;
        $toInput = $_POST['to'] ?? null;

        if ($fromInput === null || $toInput === null) {
            throw new HttpException('缺少预留时间', 409);
        }

        $reservedFrom = strtotime(str_replace('T', ' ', (string) $fromInput));
        $reservedTo = strtotime(str_replace('T', ' ', (string) $toInput));

        if ($reservedFrom === false || $reservedTo === false) {
            throw new HttpException('时间格式不正确', 409);
        }

        if ($reservedFrom >= $reservedTo) {
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

        $pdo = DB::connection();
        $this->ensureTransferTable($pdo);

        try {
            $pdo->beginTransaction();

            $deviceStmt = $pdo->prepare('SELECT id, status FROM devices WHERE id = :id FOR UPDATE');
            $deviceStmt->execute([':id' => $deviceId]);
            $device = $deviceStmt->fetch(PDO::FETCH_ASSOC);
            if (!$device) {
                throw new HttpException('设备不存在', 404);
            }

            if (!in_array($device['status'], ['checked_out', 'transfer_pending'], true)) {
                throw new HttpException('设备当前状态不支持转交', 409);
            }

            $userCheck = $pdo->prepare('SELECT id FROM users WHERE id = :id LIMIT 1');
            $userCheck->execute([':id' => $toUserId]);
            if (!$userCheck->fetchColumn()) {
                throw new HttpException('接收用户不存在', 404);
            }

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
                throw new HttpException('只有当前借用人或管理员可以发起转交', 403);
            }

            if ($projectId !== null) {
                $projStmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
                $projStmt->execute([':id' => $projectId]);
                if (!$projStmt->fetchColumn()) {
                    throw new HttpException('目标项目不存在', 404);
                }
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

            AuditLogger::log($actorId, 'device', $deviceId, 'transfer_request', [
                'initiator' => $actorId,
                'current_holder' => $currentHolderId,
                'to_user' => $toUserId,
                'project' => $projectId,
                'due_at' => $dueAt,
                'note' => $note,
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
            throw new HttpException('转交申请失败', 500, $exception);
        }

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
        $this->ensureTransferTable($pdo);

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

            $deviceStmt = $pdo->prepare('SELECT id, status FROM devices WHERE id = :id FOR UPDATE');
            $deviceStmt->execute([':id' => $transfer['device_id']]);
            $device = $deviceStmt->fetch(PDO::FETCH_ASSOC);
            if (!$device) {
                throw new HttpException('设备不存在', 404);
            }
            if (!in_array($device['status'], ['transfer_pending', 'checked_out'], true)) {
                throw new HttpException('设备当前状态不支持转交', 409);
            }

            $checkoutStmt = $pdo->prepare(
                'SELECT * FROM checkouts WHERE id = :id FOR UPDATE'
            );
            $checkoutStmt->execute([':id' => $transfer['from_checkout_id']]);
            $checkout = $checkoutStmt->fetch(PDO::FETCH_ASSOC);
            if (!$checkout || $checkout['return_at'] !== null) {
                throw new HttpException('原借用记录不存在或已结束', 409);
            }

            $projectId = $projectIdOverride
                ?? ($transfer['target_project_id'] ? (int) $transfer['target_project_id'] : null)
                ?? ($checkout['project_id'] ? (int) $checkout['project_id'] : null);

            if ($projectId !== null) {
                $projStmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id LIMIT 1');
                $projStmt->execute([':id' => $projectId]);
                if (!$projStmt->fetchColumn()) {
                    throw new HttpException('目标项目不存在', 404);
                }
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
                ':note' => $note ? "转交接收：{$note}" : '转交接收',
            ]);

            $updateTransfer = $pdo->prepare(
                'UPDATE device_transfers
                 SET status = "accepted", confirmed_at = NOW(), target_project_id = :project_id, target_due_at = :due_at
                 WHERE id = :id'
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

            AuditLogger::log($actorId, 'device', (int) $transfer['device_id'], 'transfer_confirm', [
                'from_user' => $transfer['from_user_id'],
                'to_user' => $transfer['to_user_id'],
                'project' => $projectId,
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
            throw new HttpException('确认转交失败', 500, $exception);
        }

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
        $this->ensureTransferTable($pdo);

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

    public function deleteCheckout(): string
    {
        $actorId = $this->requireActor();
        if (!$this->actorIsAdmin()) {
            throw new HttpException('未登录或无权限', 403);
        }

        $checkoutId = $this->requirePositiveInt('checkout_id');

        $pdo = DB::connection();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT device_id FROM checkouts WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $checkoutId]);
            $checkout = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$checkout) {
                throw new HttpException('借用记录不存在', 404);
            }

            $deviceId = (int) $checkout['device_id'];

            $delete = $pdo->prepare('DELETE FROM checkouts WHERE id = :id LIMIT 1');
            $delete->execute([':id' => $checkoutId]);

            DeviceStatusService::refresh($pdo, $deviceId);

            $pdo->commit();

            AuditLogger::log($actorId, 'checkout', $checkoutId, 'delete', [
                'device_id' => $deviceId,
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
            throw new HttpException('删除借用记录失败', 500, $exception);
        }

        return Response::ok();
    }

    public function cancelTransfer(): string
    {
        $actorId = $this->requireActor();
        $transferId = $this->requirePositiveInt('transfer_id');

        $pdo = DB::connection();
        $isAdminActor = $this->actorIsAdmin();

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT * FROM device_transfers WHERE id = :id FOR UPDATE');
            $stmt->execute([':id' => $transferId]);
            $transfer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transfer) {
                throw new HttpException('转交请求不存在', 404);
            }

            if ($transfer['status'] !== 'pending') {
                throw new HttpException('转交请求已处理', 409);
            }

            if ((int) $transfer['from_user_id'] !== $actorId && !$isAdminActor) {
                throw new HttpException('只有发起人或管理员可以取消转交', 403);
            }

            $update = $pdo->prepare(
                'UPDATE device_transfers SET status = "cancelled", cancelled_at = NOW() WHERE id = :id'
            );
            $update->execute([':id' => $transferId]);

            DeviceStatusService::refresh($pdo, (int) $transfer['device_id']);

            $pdo->commit();

            AuditLogger::log($actorId, 'device', (int) $transfer['device_id'], 'transfer_cancel', [
                'transfer_id' => $transferId,
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
            throw new HttpException('取消转交失败', 500, $exception);
        }

        return Response::ok();
    }
}
