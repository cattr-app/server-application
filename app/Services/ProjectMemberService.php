<?php

namespace App\Services;

use App\Models\Project;

class ProjectMemberService
{
    /**
     * @var Project
     */
    protected Project $project;

    /**
     * ProjectMemberService constructor.
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @param int $projectId
     * @return array
     */
    public function getMembers(int $projectId): array
    {
        return $this->project
            ->select('id')
            ->where('id', $projectId)
            ->with('users')
            ->first()
            ->toArray();
    }

    /**
     * @param int $projectId
     * @param array $users
     * @return array
     */
    public function syncMembers(int $projectId, array $users): array
    {
        return $this->project->find($projectId)->users()->sync($users);
    }
}
