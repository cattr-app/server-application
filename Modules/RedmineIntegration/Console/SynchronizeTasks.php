<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\Property;
use App\Models\Task;
use App\User;
use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine;

/**
 * Class SynchronizeTasks
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizeTasks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tasks from redmine for all users, who activated redmine integration.';

    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * @var ProjectRepository
     */
    protected $projectRepo;

    /**
     * @var TaskRepository
     */
    protected $taskRepo;

    /**
     * Create a new command instance.
     *
     * @param UserRepository $userRepo
     * @param ProjectRepository $projectRepo
     * @param TaskRepository $taskRepo
     */
    public function __construct(UserRepository $userRepo, ProjectRepository $projectRepo, TaskRepository $taskRepo)
    {
        parent::__construct();

        $this->userRepo = $userRepo;
        $this->projectRepo = $projectRepo;
        $this->taskRepo = $taskRepo;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->synchronizeTasks();
    }

    /**
     * Synchronize tasks for all users
     */
    public function synchronizeTasks()
    {
        $users = User::all();

        foreach ($users as $user) {
            try {
                $this->synchronizeUserNewTasks($user->id);
                $this->synchronizeUserTasks($user->id);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Synchronize tasks for current user
     *
     * @param int $userId User's id in our system
     *
     */
    public function synchronizeUserNewTasks(int $userId)
    {
        $userNewRedmineTasks = $this->userRepo->getUserNewRedmineTasks($userId);

        $client = $this->initRedmineClient($userId);

        foreach ($userNewRedmineTasks as $task) {
            $redmineTask = $this->createRedmineTask($client, $task);
            $this->taskRepo->setRedmineId($task->id, (int)$redmineTask->id);
            $this->taskRepo->markAsOld($task->id);

        }
    }

    /**
     * Create task in Redmine
     *
     * @param Redmine\Client $client
     * @param $task
     *
     * @return \SimpleXMLElement
     */
    public function createRedmineTask(Redmine\Client $client, $task)
    {
        return $client->issue->create(
            [
                'subject'        => $task->task_name,
                'description'    => $task->description,
                'project_id'     => $this->projectRepo->getRedmineProjectId($task->project_id),
                'author_id'      => $this->userRepo->getUserRedmineId($task->assigned_by),
                'assigned_to_id' => $this->userRepo->getUserRedmineId($task->user_id),
                'status_id'      => 1
            ]
        );
    }

    /**
     * Synchronize task's for current user
     *
     * @param int $userId
     * @return array
     */
    public function synchronizeUserTasks(int $userId): array
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

            //if task already exists => check if task's properties is changed
            if ($taskExist != null) {
                $task = Task::find($taskExist->entity_id);
                $is_changed = false;
                $data = [
                    'task_name'   => $taskFromRedmine['subject'],
                    'description' => $taskFromRedmine['description'],
                    'active'      => $taskFromRedmine['status']['id'],
                    'user_id'     => $userId,
                ];

                foreach ($data as $key => $value) {
                    if ($task->$key !== $value) {
                        $task->$key = $value;
                        $is_changed = true;
                    }
                }

                if ($is_changed) {
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
        $changedTasksCounter += $this->checkClosedTasks($userId, $synchronizedTasks);

        return [
            'added_tasks'   => $addedTasksCounter,
            'changed_tasks' => $changedTasksCounter
        ];
    }


    protected function checkClosedTasks(int $userId, array $redmineSynchronizedTasks): int
    {
        $userLocalRedmineTasksIds = $this->userRepo->getUserRedmineTasks($userId);
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

    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
