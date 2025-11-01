<?php

namespace App\Domain\Services;

use App\Domain\Events\AssetStatusTransitioned;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Gate;
use Sebdesign\SM\Facade as StateMachine;

class AssetService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly Dispatcher $events,
    ) {}

    public function assignToUser(Asset $asset, User $targetUser, User $actor, ?string $requestId = null): Asset
    {
        Gate::forUser($actor)->authorize('assign', $asset);

        $context = [
            'actor' => $actor,
            'target_user' => $targetUser,
            'target_user_id' => $targetUser->id,
            'request_id' => $requestId ?? request()->header('X-Request-Id'),
        ];

        $from = $asset->status;

        return $this->database->transaction(function () use ($asset, $targetUser, $actor, $context, $from) {
            /** @var \Sebdesign\SM\StateMachine\StateMachine $stateMachine */
            $stateMachine = StateMachine::get($asset, 'asset');
            $stateMachine->apply('assign_to_user', false, $context);

            $asset->current_user_id = $targetUser->id;
            $asset->updated_by = $actor->id;
            $asset->save();

            $reloaded = $asset->fresh() ?? $asset;

            $event = new AssetStatusTransitioned(
                asset: $reloaded,
                from: $from,
                to: $asset->status,
                actor: $actor,
                context: $context,
            );

            $this->events->dispatch($event);

            return $asset;
        });
    }
}
