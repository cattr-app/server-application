<?php

namespace Modules\RedmineIntegration\Helpers;

use App\Models\Property;
use App\Models\Task;
use App\User;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;

class TaskIntegrationHelper extends AbstractIntegrationHelper
{
    /**
     * Synchronize tasks for all users
     *
     * @param UserRepository $repo
     */
    public function synchronizeTasks(UserRepository $repo)
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->synchronizeUserTasks($user->id, $repo);
        }
    }

    /**
     * Synchronize tasks for current user
     *
     * @param int $userId User's id in our system
     * @param UserRepository $userRepo
     * @return array
     */
    public function synchronizeUserTasks(int $userId, UserRepository $userRepo): array
    {
        $client = $this->initRedmineClient($userId);

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

            $taskExist = (new \App\Models\Property)->where([
                ['entity_type', '=', Property::TASK_CODE],
                ['name', '=', 'REDMINE_ID'],
                ['value', '=', $taskFromRedmine['id']]
            ])->first();

            //if task already exists => check task's assigned user
            if ($taskExist != null) {
                $task = Task::find($taskExist->entity_id);

                //if task assigned to other user in our system => set current user to task's user
                if ($task->user_id != $userId) {
                    $task->user_id = $userId;
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
                    'user_id'     => $userId,
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
        $changedTasksCounter += $this->checkClosedTasks($userId, $synchronizedTasks, $userRepo);

        return [
            'added_tasks'   => $addedTasksCounter,
            'changed_tasks' => $changedTasksCounter
        ];
    }

    /**
     * If any task has been closed in Redmine => deactivate this tasks in our system
     *
     * @param int $userId User's id in our system
     * @param array $redmineSynchronizedTasks tasks, which have been returned by Redmine
     * @param $userRepo
     * @return int
     */
    protected function checkClosedTasks(int $userId, array $redmineSynchronizedTasks, $userRepo): int
    {
        $userLocalRedmineTasksIds = $userRepo->getUserRedmineTasks($userId);

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
}