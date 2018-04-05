<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Task;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskRedmineController extends AbstractRedmineController
{
    /**
     * Synchronize Redmine tasks with AmazingTime tasks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
        $changedTasksCounter = 0;
        $synchronizedTasks = [];

        foreach ($tasks as $taskFromRedmine) {
            $synchronizedTasks[] = $taskFromRedmine['id'];

            $taskExist = Property::where([
                ['entity_type', '=', Property::TASK_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['id']]
            ])->first();

            //if task already exists => check task's assigned user
            if ($taskExist != null) {
                $task = Task::find($taskExist->entity_id);

                //if task assigned to other user in our system => set current user to task's user
                if ($task->user_id != $user->id) {
                    $task->user_id = $user->id;
                    $task->save();

                    $changedTasksCounter++;
                }

                //if task is inactive in our system => activate task in our system
                if ($task->active == 0) {
                    $task->active = 1;
                    $task->save();

                    $changedTasksCounter++;
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

        //check: if any task has been closed
        $changedTasksCounter += $this->checkClosedTasks($user->id, $synchronizedTasks);

        return response()->json(
            [
                'added_tasks'    => $addedTasksCounter,
                'changed_tasks' => $changedTasksCounter
            ],
            200
        );
    }

    /**
     * If any task has been closed in Redmine => deactivate this tasks in our system
     * @param $userId
     * @param $redmineSynchronizedTasks
     * @return int
     */
    public function checkClosedTasks($userId, $redmineSynchronizedTasks)
    {
        $userLocalRedmineTasksIds = $this->getUserRedmineTasks($userId);

        $closedTasksCounter = 0;

        foreach ($userLocalRedmineTasksIds as $taskIds) {
            if (!in_array($taskIds->redmine_id, $redmineSynchronizedTasks)) {
                $localTask = Task::findOrFail($taskIds->task_id);

                if ($localTask && $localTask->active == 1) {
                    $localTask->active = 0;
                    $localTask->save();

                    $closedTasksCounter++;
                }
            }
        }

        return $closedTasksCounter;
    }

    public function getUserRedmineTasks($userId)
    {
        return DB::table('properties as prop')
            ->select('prop.value as redmine_id', 'prop.entity_id as task_id')
            ->join('tasks as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'REDMINE_ID')
            ->where('t.user_id', '=', (int)$userId)->get();
    }

}
