<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function viewAny(): bool
    {
        return true;
    }
}
