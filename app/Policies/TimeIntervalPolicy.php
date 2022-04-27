<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimeIntervalPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
    }

    /**
     * Determine if the given time interval can be viewed by the user.
     *
     * @param User $user
     * @param TimeInterval $timeInterval
     * @return bool
     */
    public function view(User $user, TimeInterval $timeInterval): bool
    {
        return cache()->remember(
            "role_user_interval_{$user->id}_$timeInterval->id",
            config('cache.role_caching_ttl'),
            static fn() => TimeInterval::whereId($timeInterval->id)->exists(),
        );
    }

    /**
     * Determine if the given time interval can be created by the user.
     *
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    public function create(User $user, int $projectId): bool
    {
        return $user->hasProjectRole('manager', $projectId);
    }

    /**
     * Determine if the given time interval can be updated by the user.
     *
     * @param User $user
     * @param TimeInterval $timeInterval
     * @return bool
     */
    public function update(User $user, TimeInterval $timeInterval): bool
    {
        return $user->id === $timeInterval->user_id;
    }

    /**
     * Determine if the given time intervals can be updated by the user.
     *
     * @param User $user
     * @param array $timeIntervalIds
     * @return bool
     */
    public function bulkUpdate(User $user, array $timeIntervalIds): bool
    {
        foreach ($timeIntervalIds as $id) {
            $can = $user->can('update', TimeInterval::find($id));

            if (!$can) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the given time interval can be destroyed by the user.
     *
     * @param User $user
     * @param TimeInterval $timeInterval
     * @return bool
     */
    public function destroy(User $user, TimeInterval $timeInterval): bool
    {
        return $user->id === $timeInterval->user_id;
    }

    /**
     * Determine if the given time intervals can be destroyed by the user.
     *
     * @param User $user
     * @param array $timeIntervalIds
     * @return bool
     */
    public function bulkDestroy(User $user, array $timeIntervalIds): bool
    {
        foreach ($timeIntervalIds as $id) {
            $can = $user->can('destroy', TimeInterval::find($id));

            if (!$can) {
                return false;
            }
        }

        return true;
    }
}
