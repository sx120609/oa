<?php

namespace App\Domain\Events;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetStatusTransitioned
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly Asset $asset,
        public readonly string $from,
        public readonly string $to,
        public readonly User $actor,
        public readonly array $context = [],
    ) {}
}
