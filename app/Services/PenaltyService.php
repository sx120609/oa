<?php

declare(strict_types=1);

namespace App\Services;

final class PenaltyService
{
    public static function ensureEligibleForCheckout(int $userId): void
    {
        // TODO: Implement penalty threshold logic (penalties table, configurable lookback window).
        // For now, no restrictions; hook remains for future enforcement.
        return;
    }
}
