<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Project;
use App\Models\Property;

class ProjectRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getRedmineClientPropertyName()
    {
        return 'project';
    }

    /**
     * Synchronize Redmine projects with AmazingTime projects
     */
    public function synchronize()
    {
        $projectsData = $this->client->project->all([
            'limit' => 1000
        ]);

        $projects = $projectsData['projects'];

        foreach ($projects as $projectFromRedmine) {
            //if project already exists => continue
            $projectExist = Property::where([
                ['entity_type', '=', Property::PROJECT_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $projectFromRedmine['id']]
            ])->first();

            if ($projectExist != null) {
                continue;
            }

            $projectInfo = [
                'company_id'  => 4,
                'name'        => $projectFromRedmine['name'],
                'description' => $projectFromRedmine['description']
            ];

            $project = Project::create($projectInfo);

            Property::create(['entity_id'   => $project->id,
                              'entity_type' => Property::PROJECT_CODE,
                              'name'        => 'REDMINE_ID',
                              'value'       => $projectFromRedmine['id']
            ]);
        }
    }


}
