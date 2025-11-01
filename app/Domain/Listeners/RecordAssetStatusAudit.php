<?php

namespace App\Domain\Listeners;

use App\Domain\Events\AssetStatusTransitioned;
use App\Models\User;
use App\Services\AuditLogger;

class RecordAssetStatusAudit
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(AssetStatusTransitioned $event): void
    {
        $targetUserId = $event->context['target_user_id'] ?? null;

        if ($targetUserId === null && ($event->context['target_user'] ?? null) instanceof User) {
            $targetUserId = $event->context['target_user']->id;
        }

        $this->auditLogger->log(
            user: $event->actor,
            auditable: $event->asset,
            action: 'asset.status_transition',
            description: 'Asset status transitioned.',
            payload: [
                'from' => $event->from,
                'to' => $event->to,
                'target_user_id' => $targetUserId,
                'request_id' => $event->context['request_id'] ?? null,
            ],
        );
    }
}
