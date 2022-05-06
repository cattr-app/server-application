<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function view(User $user, Project $project): bool
    {
        return cache()->remember(
            "role_user_project_{$user->id}_$project->id",
            config('cache.role_caching_ttl'),
            static fn() => Project::whereId($project->id)->exists()
        );
    }

    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('manager');
    }

    public function update(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        return $user->hasRole('manager') || $user->hasProjectRole('manager', $project->id);
    }

    public function updateMembers(User $user, Project $project): bool
    {
        return $user->hasRole('manager') || $user->hasProjectRole('manager', $project->id);
    }

    public function destroy(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        return $user->hasRole('manager') || $user->hasProjectRole('manager', $project->id);
    }
}
