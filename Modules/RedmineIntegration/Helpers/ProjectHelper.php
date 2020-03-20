<?php

namespace Modules\RedmineIntegration\Helpers;

use App\Models\Project;
use App\Models\Property;
use Exception;

class ProjectHelper
{
    protected Project $project;
    protected Property $property;

    public function __construct(
        Project $project,
        Property $property
    ) {
        $this->project = $project;
        $this->property = $property;
    }

    /**
     * @throws Exception
     */
    public function getProjectByRedmineId(int $projectRedmineId): Project
    {
        $projectEav = $this->property->getProperty('project', 'REDMINE_ID', [
            'value' => $projectRedmineId
        ])
            ->first();

        if (!$projectEav) {
            throw new Exception("There is no project task with id $projectRedmineId");
        }

        $internalProjectId = $projectEav->entity_id;

        return $this->project->findOrFail($internalProjectId);
    }
}
