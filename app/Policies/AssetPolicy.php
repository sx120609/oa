<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

class AssetPolicy
{
    public function assign(User $user, Asset $asset): bool
    {
        return (bool) $user->is_admin;
    }
}
