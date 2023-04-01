<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Cache;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
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

    /**
     * Determine if the given task can be viewed by the user.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        return Cache::store('octane')->remember(
            "role_user_task_{$user->id}_$task->id",
            config('cache.role_caching_ttl'),
            static fn() => Task::whereId($task->id)->exists(),
        );
    }

    /**
     * Determine if the given task can be created by the user.
     *
     * @param User $user
     * @param int $projectId
     * @return bool
     */
    public function create(User $user, int $projectId): bool
    {
        if (optional(Project::find($projectId))->source !== 'internal') {
            return false;
        }

        return $user->hasRole(Role::MANAGER)
            || $user->hasProjectRole([Role::MANAGER, Role::USER], $projectId);
    }

    /**
     * Determine if the given task can be updated by the user.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function update(User $user, Task $task): bool
    {
        if (isset($task->project) && $task->project->source !== 'internal') {
            return false;
        }

        return $user->hasRole(Role::MANAGER)
            || $user->hasProjectRole(Role::MANAGER, $task->project_id)
            || ($user->hasProjectRole(Role::USER, $task->project_id) && $task->assigned_by === $user->id);
    }

    /**
     * Determine if the given task can be destroyed by the user.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function destroy(User $user, Task $task): bool
    {
        if (isset($task->project) && $task->project->source !== 'internal') {
            return false;
        }

        return $user->hasRole(Role::MANAGER)
            || $user->hasProjectRole(Role::MANAGER, $task->project_id);
    }
}
