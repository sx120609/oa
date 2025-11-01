<?php

namespace App\Domain\StateMachines\Guards;

use App\Models\Asset;
use App\Models\User;
use Sebdesign\SM\Event\TransitionEvent;

class AssetStateGuard
{
    public static function canAssignToUser(Asset $asset, TransitionEvent $event): bool
    {
        $context = $event->getContext();

        $actor = $context['actor'] ?? null;
        $target = $context['target_user'] ?? null;

        if (! $actor instanceof User || ! $target instanceof User) {
            return false;
        }

        if ($asset->status !== Asset::STATUS_IN_STOCK) {
            return false;
        }

        if ($asset->current_user_id && $asset->current_user_id !== $target->id) {
            return false;
        }

        return true;
    }
}
