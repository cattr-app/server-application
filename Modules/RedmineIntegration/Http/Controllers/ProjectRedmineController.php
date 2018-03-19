<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Project;
use App\Models\Property;
use Illuminate\Http\Request;
use Redmine;

class ProjectRedmineController extends AbstractRedmineController
{
    /**
     * Synchronize Redmine projects with AmazingTime projects
     */
    public function synchronize(Request $request)
    {
        $user = auth()->user();

        $client = $this->initRedmineClient($user->id);

        $projectsData = $client->project->all([
            'limit' => 1000
        ]);

        $projects = $projectsData['projects'];

        $addedProjectsCounter = 0;

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
            $addedProjectsCounter++;

            Property::create([
                'entity_id'   => $project->id,
                'entity_type' => Property::PROJECT_CODE,
                'name'        => 'REDMINE_ID',
                'value'       => $projectFromRedmine['id']
            ]);
        }

        return response()->json([
                'added_projects' => $addedProjectsCounter
            ], 200
        );
    }


}
