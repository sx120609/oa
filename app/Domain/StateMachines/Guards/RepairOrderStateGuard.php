<?php

namespace App\Domain\StateMachines\Guards;

use App\Models\RepairOrder;
use App\Models\User;
use Sebdesign\SM\Event\TransitionEvent;

class RepairOrderStateGuard
{
    public static function ensureActorProvided(RepairOrder $order, TransitionEvent $event): bool
    {
        $context = $event->getContext();

        return ($context['actor'] ?? null) instanceof User;
    }
}
