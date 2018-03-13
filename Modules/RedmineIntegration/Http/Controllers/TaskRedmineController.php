<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Task;
use App\Models\Property;

class TaskRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets Issue with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->issue->show($id));
    }

    /**
     * Gets list of issues
     */
    public function list()
    {
        dd($this->client->issue->all([
            'limit' => 1000
        ]));
    }

    /**
     * Returns issues from project with id == $projectId
     *
     * @param $projectId
     */
    public function getProjectIssues($projectId)
    {
        dd($this->client->issue->all([
            'project_id' => $projectId
        ]));
    }

    /**
     * Returns issues assigned to user with id == $userId
     *
     * @param $userId
     */
    public function getUserIssues($userId)
    {
        dd($this->client->issue->all([
            'assigned_to_id' => $userId
        ]));
    }

    public function synchronize()
    {
        $tasksData = $this->client->issue->all([
            'limit' => 1000
        ]);

        $tasks = $tasksData['issues'];

        foreach ($tasks as $taskFromRedmine) {
            $taskExist = Property::where([
                ['entity_type', '=', Property::TASK_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['id']]
            ])->first();

            if ($taskExist != null) {
                continue;
            }

            $projectProperty = Property::where([
                ['entity_type', '=', Property::PROJECT_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['project']['id']]
            ])->first();

            if ($projectProperty && $projectProperty->entity_id) {
                $taskInfo = [
                    'project_id'  => $projectProperty->entity_id,
                    'task_name'   => $taskFromRedmine['subject'],
                    'description' => $taskFromRedmine['description'],
                    'active' => 1,
                    'user_id' => 1,
                    'assigned_by' => 1,
                    'url' => 'url',
                ];

                $task = Task::create($taskInfo);

                Property::create(['entity_id' => $task->id, 'entity_type' => Property::TASK_CODE, 'name' => 'REDMINE_ID', 'value' => $taskFromRedmine['id']]);
            }
        }
    }
}
