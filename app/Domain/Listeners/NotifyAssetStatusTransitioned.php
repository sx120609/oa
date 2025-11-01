<?php

namespace App\Domain\Listeners;

use App\Domain\Events\AssetStatusTransitioned;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotifyAssetStatusTransitioned
{
    public function handle(AssetStatusTransitioned $event): void
    {
        $targetUserId = $event->context['target_user_id'] ?? null;

        if ($targetUserId === null && ($event->context['target_user'] ?? null) instanceof User) {
            $targetUserId = $event->context['target_user']->id;
        }

        Log::info('asset.status_transitioned', [
            'asset_id' => $event->asset->id,
            'from' => $event->from,
            'to' => $event->to,
            'actor_id' => $event->actor->id,
            'target_user_id' => $targetUserId,
            'request_id' => $event->context['request_id'] ?? null,
        ]);
    }
}
