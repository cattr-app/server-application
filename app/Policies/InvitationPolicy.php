<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\User;
use App\Models\Invitation;

class InvitationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $user->hasRole('manager') || $user->hasRole('auditor') || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('manager') || $user->hasRole('auditor');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('manager') || $user->hasRole('auditor');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->isAdmin() || $user->hasRole('manager') || $user->hasRole('auditor');
    }
}
