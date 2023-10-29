<?php

namespace App\Traits;

use App\Enums\Role;
use Cache;

trait HasRole
{
    /**
     * Determine if the user has admin role.
     *
     * @return bool
     */
    final public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Determine if the user has role.
     *
     * @param Role $role
     * @return bool
     */
    final public function hasRole(Role|array $role): bool
    {
        if (is_array($role)) {
            foreach ($role as $e) {
                if ($this->role_id === $e->value) {
                    return true;
                }
            }

            return false;
        }

        return $this->role_id === $role->value;
    }

    /**
     * Returns the user role in the project.
     *
     * @param $projectId
     * @return int|null
     */
    final public function getProjectRole(int $projectId): ?int
    {
        $project = self::projects()
            ->where(['project_id' => $projectId])
            ->first();

        return optional(optional($project)->pivot)->role_id;
    }

    /**
     * Determine if the user has a role in the project.
     *
     * @param Role|array $role
     * @param int $projectId
     * @return bool
     */
    final public function hasProjectRole(Role|array $role, int $projectId): bool
    {
        $self = $this;
        $roles = Cache::store('octane')->remember(
            "role_project_$self->id",
            config('cache.role_caching_ttl'),
            static fn() => $self->projectsRelation()
                ->get(['role_id', 'project_id'])
                ->keyBy('project_id')
                ->map(static fn($el) => $el->role_id)
                ->all(),
        );

        if (!isset($roles[$projectId])) {
            return false;
        }

        if (is_array($role)) {
            foreach ($role as $e) {
                if ($roles[$projectId] === $e->value) {
                    return true;
                }
            }
        } elseif ($roles[$projectId] === $role->value) {
            return true;
        }

        if ($role === Role::ANY) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user has a role in any project.
     *
     * @param Role|array $role
     * @return bool
     */
    final public function hasRoleInAnyProject(Role|array $role): bool
    {
        $self = $this;
        $roles = Cache::store('octane')->remember(
            "role_any_project_$self->id",
            config('cache.role_caching_ttl'),
            static fn() => $self->projectsRelation()
                ->get(['role_id'])
                ->keyBy('role_id')
                ->map(static fn($el) => $el->role_id)
                ->all(),
        );

        if (is_array($role)) {
            foreach ($role as $e) {
                if (isset($roles[$e->value])) {
                    return true;
                }
            }
        } elseif (isset($roles[$role->value])) {
            return true;
        }

        return false;
    }
}
