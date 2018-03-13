<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Task;
use Modules\RedmineIntegration\Entities\RedmineProject;
use Modules\RedmineIntegration\Entities\RedmineTask;

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
            $taskExist = RedmineTask::where('redmine_task_id', '=', $taskFromRedmine['id'])->first();

            if ($taskExist != null) {
                continue;
            }

            $project = RedmineProject::where('redmine_project_id', '=', $taskFromRedmine['project']['id'])->first();

            if ($project && $project->project_id) {
                $taskInfo = [
                    'project_id'  => $project->project_id,
                    'task_name'   => $taskFromRedmine['subject'],
                    'description' => $taskFromRedmine['description'],
                    'active' => 1,
                    'user_id' => 1,
                    'assigned_by' => 1,
                    'url' => 'url',
                ];
            }

            $task = Task::create($taskInfo);

            RedmineTask::create(['task_id' => $task->id, 'redmine_task_id' => $taskFromRedmine['id']]);
        }
    }
}
