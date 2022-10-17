<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\TimeInterval;
use App\Models\User;
use Cache;
use Illuminate\Contracts\Database\Query\Builder;

class TimeIntervalPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, TimeInterval $timeInterval): bool
    {
        return Cache::store('octane')->remember(
            "role_user_interval_{$user->id}_$timeInterval->id",
            config('cache.role_caching_ttl'),
            static fn() => TimeInterval::whereId($timeInterval->id)->exists(),
        );
    }

    public function create(User $user, int $userId, int $taskId, bool $isManual): bool
    {
        $projectId = self::getProjectIdByTaskId($taskId);

        if ($isManual) {
            if ((bool)$user->manual_time === false) {
                return false;
            }

            if ($user->id !== $userId) {
                return $user->hasRole('manager') || $user->hasProjectRole('manager', $projectId);
            }

            return (
                $user->hasProjectRole('user', $projectId)
                || $user->hasProjectRole('manager', $projectId)
                || $user->hasRole('manager')
            );
        }

        return $user->hasProjectRole('user', $projectId);
    }

    public function update(User $user, TimeInterval $timeInterval): bool
    {
        return $user->id === $timeInterval->user_id;
    }

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

    private static function getProjectIdByTaskId(int $taskId): int
    {
        return Cache::store('octane')->remember(
            "role_project_of_task_$taskId",
            config('cache.role_caching_ttl'),
            static fn() => Project::whereHas(
                'tasks',
                static fn(Builder $query) => $query->where('id', '=', $taskId)
            )->firstOrFail()->id
        );
    }
}
