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

    /**
     * Gets project with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->project->show($id));
    }

    /**
     * Gets list of projects
     */
    public function list()
    {
        dd($this->client->project->all([
            'limit' => 1000
        ]));
    }

    public function synchronize()
    {
        $projectsData = $this->client->project->all([
            'limit' => 1000
        ]);

        $projects = $projectsData['projects'];

        foreach ($projects as $projectFromRedmine) {
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

            Property::create(['entity_id' => $project->id, 'entity_type' => Property::PROJECT_CODE, 'name' => 'REDMINE_ID', 'value' => $projectFromRedmine['id']]);
        }
    }
}
