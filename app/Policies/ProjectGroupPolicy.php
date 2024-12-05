<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectGroupPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::MANAGER);
    }

    public function update(User $user): bool
    {
        return $user->hasRole(Role::MANAGER);
    }

    public function destroy(User $user): bool
    {
        return $user->hasRole(Role::MANAGER);
    }
}
