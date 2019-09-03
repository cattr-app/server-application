<?php

namespace Modules\RedmineIntegration\Helpers;

use App\Models\Project;
use App\Models\Property;

class ProjectHelper
{
    /**
     * @var Project
     */
    protected $project;
    /**
     * @var Property
     */
    protected $property;

    /**
     * ProjectHelper constructor.
     *
     * @param  Project   $project
     * @param  Property  $property
     */
    public function __construct(
        Project $project,
        Property $property
    ) {
        $this->project = $project;
        $this->property = $property;
    }

    /**
     * @param  int  $projectRedmineId
     *
     * @return Project
     * @throws \Exception
     */
    public function getProjectByRedmineId(int $projectRedmineId): Project
    {
        $projectEav = $this->property->getProperty('project', 'REDMINE_ID', [
            'value' => $projectRedmineId
        ])
            ->first();

        if (!$projectEav) {
            throw new \Exception("There is no project task with id $projectRedmineId");
        }

        $internalProjectId = $projectEav->entity_id;

        return $this->project->findOrFail($internalProjectId);
    }
}
