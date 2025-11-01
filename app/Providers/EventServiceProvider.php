<?php

namespace App\Providers;

use App\Domain\Events\AssetStatusTransitioned;
use App\Domain\Listeners\NotifyAssetStatusTransitioned;
use App\Domain\Listeners\RecordAssetStatusAudit;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        AssetStatusTransitioned::class => [
            RecordAssetStatusAudit::class,
            NotifyAssetStatusTransitioned::class,
        ],
    ];
}
