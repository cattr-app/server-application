<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Task;
use App\Models\Property;
use Illuminate\Http\Request;

class TaskRedmineController extends AbstractRedmineController
{
    /**
     * Synchronize Redmine tasks with AmazingTime tasks
     */
    public function synchronize(Request $request)
    {
        $user = auth()->user();

        $client = $this->initRedmineClient($user->id);

        //get current user's id
        $currentRedmineUser = $client->user->getCurrentUser();
        $currentRedmineUserId = $currentRedmineUser['user']['id'];

        //get tasks assigned to current user
        $tasksData = $client->issue->all([
            'limit'          => 1000,
            'assigned_to_id' => $currentRedmineUserId
        ]);

        $tasks = $tasksData['issues'];
        $addedTasksCounter = 0;

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
                $addedTasksCounter++;

                Property::create([
                    'entity_id'   => $task->id,
                    'entity_type' => Property::TASK_CODE,
                    'name'        => 'REDMINE_ID',
                    'value'       => $taskFromRedmine['id']
                ]);
            }
        }

        return response()->json([
                'added_tasks' => $addedTasksCounter
            ], 200
        );
    }

}
