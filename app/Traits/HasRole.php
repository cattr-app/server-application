<?php

namespace App\Traits;

use Cache;
use Illuminate\Database\Eloquent\Builder;

trait HasRole
{
    /**
     * Determine if the user has role.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        if ($role === 'admin') {
            return $this->isAdmin();
        }

        return $this->role->name === $role;
    }

    /**
     * Determine if the user has admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool)$this->is_admin;
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
     * @param string $role
     * @param int|null $projectId
     * @return bool
     */
    public function hasProjectRole(string $role, int $projectId = null): bool
    {
        $userId = $this->id;
        return Cache::store('octane')->remember(
            "role_project_{$role}_$projectId",
            config('cache.role_caching_ttl'),
            static fn() => self::whereHas(
                'projectsRelation',
                static fn(Builder $query) => $query
                    ->where('user_id', $userId)
                    ->when($projectId, static fn(Builder $query) => $query->where('project_id', $projectId))
                    ->whereHas('role', static fn(Builder $query) => $query->where('name', $role))
            )->exists(),
        );
    }
}
