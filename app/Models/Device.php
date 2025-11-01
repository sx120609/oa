<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    protected $fillable = [
        'asset_tag',
        'name',
        'category',
        'specification',
        'status',
        'owner_id',
        'location',
        'meta',
        'purchased_at',
        'inbounded_at',
        'assigned_at',
        'repaired_at',
        'scrapped_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'purchased_at' => 'datetime',
        'inbounded_at' => 'datetime',
        'assigned_at' => 'datetime',
        'repaired_at' => 'datetime',
        'scrapped_at' => 'datetime',
    ];

    /**
     * @phpstan-return BelongsTo<User, Device>
     */
    public function owner(): BelongsTo
    {
        /** @var BelongsTo<User, Device> $relation */
        $relation = $this->belongsTo(User::class, 'owner_id');

        return $relation;
    }

    /**
     * @phpstan-return HasMany<DeviceTransaction, Device>
     */
    public function transactions(): HasMany
    {
        /** @var HasMany<DeviceTransaction, Device> $relation */
        $relation = $this->hasMany(DeviceTransaction::class);

        return $relation;
    }
}
