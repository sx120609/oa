<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceTransaction extends Model
{
    protected $fillable = [
        'no',
        'device_id',
        'user_id',
        'type',
        'status_before',
        'status_after',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * @phpstan-return BelongsTo<Device, DeviceTransaction>
     */
    public function device(): BelongsTo
    {
        /** @var BelongsTo<Device, DeviceTransaction> $relation */
        $relation = $this->belongsTo(Device::class);

        return $relation;
    }

    /**
     * @phpstan-return BelongsTo<User, DeviceTransaction>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, DeviceTransaction> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }
}
