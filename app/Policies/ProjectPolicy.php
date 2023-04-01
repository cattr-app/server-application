<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->isAdmin() ?: null;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasProjectRole(Role::ANY, $project->id);
    }

    public function viewAny(): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::MANAGER);
    }

    public function update(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        return $user->hasRole(Role::MANAGER) || $user->hasProjectRole(Role::MANAGER, $project->id);
    }

    public function updateMembers(User $user, Project $project): bool
    {
        return $user->hasRole(Role::MANAGER) || $user->hasProjectRole(Role::MANAGER, $project->id);
    }

    public function destroy(User $user, Project $project): bool
    {
        if ($project->source !== 'internal') {
            return false;
        }

        return $user->hasRole(Role::MANAGER);
    }
}
