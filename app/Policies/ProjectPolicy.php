<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the given project can be viewed by the user.
     *
     * The user can see the project if it exists in the user's scope.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function view(User $user, Project $project): bool
    {
        return Project::find(optional($project)->id)->exists();
    }

    /**
     * Determine if the given project can be created by the user.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    /**
     * Determine if the given project can be updated by the user.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function update(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $project->id)) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function updateMembers(User $user, Project $project): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $project->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the given project can be destroyed by the user.
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function destroy(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasProjectRole('manager', $project->id)) {
            return true;
        }

        return false;
    }
}
