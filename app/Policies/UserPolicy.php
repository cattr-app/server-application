<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool
     */
    public function before(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    /**
     * Determine if the given user can be viewed by the user.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function view(User $user, User $model): bool
    {
        return cache()->remember(
            "role_user_user_{$user->id}_$model->id",
            config('cache.role_caching_ttl'),
            static fn () => User::whereId($model->id)->exists(),
        );
    }

    /**
     * Determine if the given user can be created by the user.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function create(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the given user can be updated by the user.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    /**
     * Determine if the given user can be destroyed by the user.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function destroy(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine if the given user can be invited by the user.
     *
     * @param User $user
     * @param User $model
     * @return bool
     */
    public function sendInvite(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }
}
