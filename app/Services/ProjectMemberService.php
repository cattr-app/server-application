<?php

namespace App\Services;

use App\Models\Project;

class ProjectMemberService
{
    public static function getMembers(int $projectId): array
    {
        return Project::select('id')
            ->where('id', $projectId)
            ->with('users')
            ->first()
            ->toArray();
    }

    public static function syncMembers(int $projectId, array $users): array
    {
        return Project::findOrFail($projectId)->users()->sync($users);
    }
}
