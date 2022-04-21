<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given task can be viewed by the user.
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function view(User $user, Task $task): bool
    {
        return cache()->remember(
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

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $projectId)) {
            return true;
        }

        return false;
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

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $task->project_id)) {
            return true;
        }

        return false;
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

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $task->project_id)) {
            return true;
        }

        return false;
    }
}
