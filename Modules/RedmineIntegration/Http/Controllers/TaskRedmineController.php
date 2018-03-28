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

            $taskExist = Property::where([
                ['entity_type', '=', Property::TASK_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['id']]
            ])->first();

            //if task already exists => check task's assigned user
            if ($taskExist != null) {
                //if tasks assigned to other user in our system => set current user to task's user
                $task = Task::find($taskExist->entity_id);

                if ($task->user_id != $user->id) {
                    $task->user_id = $user->id;
                    $task->save();
                    $addedTasksCounter++;
                }

                continue;
            }

            $projectProperty = Property::where([
                ['entity_type', '=', Property::PROJECT_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['project']['id']]
            ])->first();

            //if task's project exists in our system => add task
            if ($projectProperty && $projectProperty->entity_id) {
                //TODO: add assigned_by from our system
                $taskInfo = [
                    'project_id'  => $projectProperty->entity_id,
                    'task_name'   => $taskFromRedmine['subject'],
                    'description' => $taskFromRedmine['description'],
                    'active'      => $taskFromRedmine['status']['id'],
                    'user_id'     => $user->id,
                    'assigned_by' => 1,
                    'url'         => 'url',
                ];

                $task = Task::create($taskInfo);
                $addedTasksCounter++;

                //Add task redmine id property
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
