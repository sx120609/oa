<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * @phpstan-return BelongsTo<User, AuditLog>
     */
    public function user(): BelongsTo
    {
        /** @var BelongsTo<User, AuditLog> $relation */
        $relation = $this->belongsTo(User::class);

        return $relation;
    }

    /**
     * @phpstan-return MorphTo<Model, AuditLog>
     */
    public function auditable(): MorphTo
    {
        /** @var MorphTo<Model, AuditLog> $relation */
        $relation = $this->morphTo();

        return $relation;
    }
}
