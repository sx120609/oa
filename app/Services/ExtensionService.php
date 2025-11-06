<?php

declare(strict_types=1);

namespace App\Services;

final class ExtensionService
{
    public static function request(int $checkoutId, int $requestedBy, string $reason): void
    {
        // TODO: Implement extension request persistence (extensions table with pending status).
    }

    public static function approve(int $extensionId, int $approvedBy): void
    {
        // TODO: Implement extension approval workflow (update checkouts.due_at, mark extension record).
    }
}
