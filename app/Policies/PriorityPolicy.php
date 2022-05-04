<?php

namespace App\Policies;

use App\Models\Priority;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PriorityPolicy
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
        return $user->hasRole('user') || $user->hasRole('manager')
            || $user->hasRole('auditor') || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param User $user
     * @param Priority $priority
     * @return bool
     */
    public function update(User $user, Priority $priority): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param User $user
     * @param Priority $priority
     * @return bool
     */
    public function delete(User $user, Priority $priority): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor');
    }
}
