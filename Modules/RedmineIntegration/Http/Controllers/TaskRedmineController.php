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

    public function getRedmineClientPropertyName()
    {
        return 'issue';
    }

    /**
     * Returns tasks from project with id == $projectId
     *
     * @param $projectId
     */
    public function getProjectTasks($projectId)
    {
        dd($this->client->issue->all([
            'project_id' => $projectId
        ]));
    }

    /**
     * Returns tasks assigned to user with id == $userId
     *
     * @param $userId
     */
    public function getUserTasks($userId)
    {
        dd($this->client->issue->all([
            'assigned_to_id' => $userId
        ]));
    }

    /**
     * Synchronize Redmine tasks with AmazingTime tasks
     */
    public function synchronize()
    {
        //get current user's id
        $currentUser = $this->client->user->getCurrentUser();
        $currentUserId = $currentUser['user']['id'];

        //get tasks assigned to current user
        $tasksData = $this->client->issue->all([
            'limit'          => 1000,
            'assigned_to_id' => $currentUserId
        ]);

        $tasks = $tasksData['issues'];

        foreach ($tasks as $taskFromRedmine) {
            //if task already exists => continue
            $taskExist = Property::where([
                ['entity_type', '=', Property::TASK_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['id']]
            ])->first();

            if ($taskExist != null) {
                continue;
            }

            //is task's project exists => add task
            $projectProperty = Property::where([
                ['entity_type', '=', Property::PROJECT_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['project']['id']]
            ])->first();

            //TODO: add user_id and assigned_by from our system
            if ($projectProperty && $projectProperty->entity_id) {
                $taskInfo = [
                    'project_id'  => $projectProperty->entity_id,
                    'task_name'   => $taskFromRedmine['subject'],
                    'description' => $taskFromRedmine['description'],
                    'active'      => $taskFromRedmine['status']['id'],
                    'user_id'     => 1,
                    'assigned_by' => 1,
                    'url'         => 'url',
                ];

                $task = Task::create($taskInfo);

                Property::create(['entity_id'   => $task->id,
                                  'entity_type' => Property::TASK_CODE,
                                  'name'        => 'REDMINE_ID',
                                  'value'       => $taskFromRedmine['id']
                ]);
            }
        }
    }

}
