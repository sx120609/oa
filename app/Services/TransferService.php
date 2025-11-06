<?php

declare(strict_types=1);

namespace App\Services;

final class TransferService
{
    public static function request(int $deviceId, int $fromUserId, int $toUserId): void
    {
        // TODO: Implement transfer request creation (persist transfer record, set device status to transfer_pending).
    }

    public static function confirm(int $transferId, int $actorId): void
    {
        // TODO: Implement transfer confirmation logic (reassign device, reset due-date countdown policy).
    }
}
