<?php

namespace App\Traits;

trait HasRole
{
    /**
     * Determine if the user has admin role.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

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
        return $this
            ->whereHas('projectsRelation', function ($query) use ($projectId, $role) {
                $query->where('user_id', $this->id);

                if ($projectId) {
                    $query->where('project_id', $projectId);
                }

                $query->whereHas('role', function ($query) use ($role) {
                    $query->where('name', $role);
                });
            })
            ->exists();
    }
}
