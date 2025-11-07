<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;

final class DeviceStatusService
{
    private const LOCKED_STATUSES = ['lost', 'repair'];

    public static function refresh(PDO $pdo, int $deviceId): void
    {
        $deviceStmt = $pdo->prepare('SELECT status FROM devices WHERE id = :id LIMIT 1');
        $deviceStmt->execute([':id' => $deviceId]);
        $device = $deviceStmt->fetch(PDO::FETCH_ASSOC);

        if ($device === false) {
            return;
        }

        $currentStatus = $device['status'] ?? 'in_stock';
        if (in_array($currentStatus, self::LOCKED_STATUSES, true)) {
            return;
        }

        $status = 'in_stock';

        if (self::hasPendingTransfer($pdo, $deviceId)) {
            $status = 'transfer_pending';
        } elseif (self::hasOpenCheckout($pdo, $deviceId)) {
            $status = 'checked_out';
        } elseif (self::hasActiveReservation($pdo, $deviceId)) {
            $status = 'reserved';
        }

        if ($status === $currentStatus) {
            return;
        }

        $update = $pdo->prepare('UPDATE devices SET status = :status WHERE id = :device_id');
        $update->execute([
            ':status' => $status,
            ':device_id' => $deviceId,
        ]);
    }

    private static function hasOpenCheckout(PDO $pdo, int $deviceId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM checkouts
             WHERE device_id = :device_id
               AND return_at IS NULL
               AND checked_out_at <= NOW()
             LIMIT 1'
        );
        $stmt->execute([':device_id' => $deviceId]);
        return (bool) $stmt->fetchColumn();
    }

    private static function hasActiveReservation(PDO $pdo, int $deviceId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM reservations
             WHERE device_id = :device_id
               AND reserved_from <= NOW()
               AND reserved_to > NOW()
             LIMIT 1'
        );
        $stmt->execute([':device_id' => $deviceId]);
        return (bool) $stmt->fetchColumn();
    }

    private static function hasPendingTransfer(PDO $pdo, int $deviceId): bool
    {
        try {
            $stmt = $pdo->prepare(
                'SELECT 1 FROM device_transfers WHERE device_id = :device_id AND status = "pending" LIMIT 1'
            );
            $stmt->execute([':device_id' => $deviceId]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $exception) {
            return false;
        }
    }
}
