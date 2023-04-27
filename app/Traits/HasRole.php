<?php

namespace App\Traits;

use App\Enums\Role;
use Cache;

trait HasRole
{
    /**
     * Determine if the user has role.
     *
     * @param Role $role
     * @return bool
     */
    public function hasRole(Role|array $role): bool
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
     * Determine if the user has admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    /**
     * Returns the user role in the project.
     *
     * @param $projectId
     * @return int|null
     */
    public function getProjectRole(int $projectId): ?int
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
    public function hasProjectRole(Role|array $role, int $projectId): bool
    {
        $self = $this;
        $roles = Cache::store('octane')->remember(
            "role_project_$self->id",
            config('cache.role_caching_ttl'),
            static fn() => $self->projectsRelation()->get()->collect()->keyBy('project_id')->all(),
        );

        if (!isset($roles[$projectId])) {
            return false;
        }

        if (is_array($role)) {
            foreach ($role as $e) {
                if ($roles[$projectId]['role_id'] === $e->value) {
                    return true;
                }
            }
        }

        if ($role === Role::ANY) {
            return true;
        }

        return $roles[$projectId]['role_id'] === $role->value;
    }
}
