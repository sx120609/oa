<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function log(User $user, Model $auditable, string $action, ?string $description = null, ?array $payload = null): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'action' => $action,
            'description' => $description,
            'payload' => $payload,
        ]);
    }
}
