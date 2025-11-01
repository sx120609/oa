<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Device $device): bool
    {
        return $user->is_admin || $device->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Device $device): bool
    {
        return $user->is_admin;
    }

    public function manage(User $user): bool
    {
        return $user->is_admin;
    }
}
