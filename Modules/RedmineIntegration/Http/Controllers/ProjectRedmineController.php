<?php

namespace Modules\RedmineIntegration\Http\Controllers;


use App\Models\Project;
use Modules\RedmineIntegration\Entities\RedmineProject;

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
        //TODO: get all projects form redmine => check projects existing [=> add project]
        $projectsData = $this->client->project->all([
            'limit' => 1000
        ]);

        $projects = $projectsData['projects'];

        foreach ($projects as $projectFromRedmine) {
            $user = RedmineProject::where('redmine_project_id', '=', $projectFromRedmine['id'])->first();

            if ($user != null) {
                continue;
            }

            $projectInfo = [
                'company_id'  => 4,
                'name'        => $projectFromRedmine['name'],
                'description' => $projectFromRedmine['description']
            ];

            $project = Project::create($projectInfo);

            RedmineProject::create(['project_id' => $project->id, 'redmine_project_id' => $projectFromRedmine['id']]);
        }
    }
}
