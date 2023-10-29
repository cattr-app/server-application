<?php

namespace App\Policies;

use App\Enums\Role;
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
        return $timeInterval->user_id === $user->id || $user->can('view', $timeInterval->task);
    }

    public function create(User $user, int $userId, int $taskId, bool $isManual): bool
    {
        $projectId = self::getProjectIdByTaskId($taskId);

        if ($isManual) {
            if ((bool)$user->manual_time === false) {
                return false;
            }

            if ($user->id !== $userId) {
                return $user->hasRole(Role::MANAGER) || $user->hasProjectRole(Role::MANAGER, $projectId);
            }

            return (
                $user->hasProjectRole([Role::USER, Role::MANAGER], $projectId)
                || $user->hasRole(Role::MANAGER)
            );
        }

        return $user->hasProjectRole([Role::USER, Role::MANAGER], $projectId);
    }

    public function update(User $user, TimeInterval $timeInterval): bool
    {
        return $user->id === $timeInterval->user_id;
    }

    public function bulkUpdate(User $user, array $timeIntervalIds): bool
    {
        foreach ($timeIntervalIds as $id) {
            if (!$user->can('update', TimeInterval::find($id))) {
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
            "project_of_task_$taskId",
            config('cache.role_caching_ttl'),
            static fn() => Project::whereHas(
                'tasks',
                static fn(Builder $query) => $query->where('id', '=', $taskId)
            )->firstOrFail()->id
        );
    }
}
