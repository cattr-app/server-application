<?php

namespace App\Services;

use App\Models\Project;

class ProjectMemberService
{
    public static function getMembers(int $projectId): array
    {
        return Project::find($projectId, 'id')
            ->where('id', $projectId)
            ->with('users')
            ->first()
            ->only(['id', 'users']);
    }

    public static function syncMembers(int $projectId, array $users): array
    {
        return Project::findOrFail($projectId)->users()->sync($users);
    }
}
