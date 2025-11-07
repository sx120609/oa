<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class DeviceStatusService
{
    public static function refresh(PDO $pdo, int $deviceId): void
    {
        $status = 'in_stock';

        $checkoutStmt = $pdo->prepare(
            'SELECT 1 FROM checkouts WHERE device_id = :device_id AND return_at IS NULL LIMIT 1'
        );
        $checkoutStmt->execute([':device_id' => $deviceId]);
        if ($checkoutStmt->fetchColumn()) {
            $status = 'checked_out';
        } else {
            $reservationStmt = $pdo->prepare(
                'SELECT 1 FROM reservations WHERE device_id = :device_id LIMIT 1'
            );
            $reservationStmt->execute([':device_id' => $deviceId]);
            if ($reservationStmt->fetchColumn()) {
                $status = 'reserved';
            }
        }

        $update = $pdo->prepare('UPDATE devices SET status = :status WHERE id = :device_id');
        $update->execute([
            ':status' => $status,
            ':device_id' => $deviceId,
        ]);
    }
}
