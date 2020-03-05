<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;
use Modules\RedmineIntegration\Models\Priority;
use Modules\RedmineIntegration\Models\Settings;
use Modules\RedmineIntegration\Models\Status;
use Modules\RedmineIntegration\Models\TimeActivity;
use Redmine;
use SimpleXMLElement;

/**
 * Class SynchronizeTasks
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
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var Priority
     */
    protected $priority;

    /**
     * Create a new command instance.
     *
     * @param  UserRepository     $userRepo
     * @param  ProjectRepository  $projectRepo
     * @param  TaskRepository     $taskRepo
     * @param  ClientFactory      $clientFactory
     * @param  Settings           $settings
     * @param  Status             $status
     * @param  Priority           $priority
     */
    public function __construct(
        UserRepository $userRepo,
        ProjectRepository $projectRepo,
        TaskRepository $taskRepo,
        ClientFactory $clientFactory,
        Settings $settings,
        Status $status,
        Priority $priority
    ) {
        parent::__construct();

        $this->userRepo = $userRepo;
        $this->projectRepo = $projectRepo;
        $this->taskRepo = $taskRepo;
        $this->clientFactory = $clientFactory;
        $this->settings = $settings;
        $this->status = $status;
        $this->priority = $priority;
    }

    /**
     * Execute the console command.
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
                $this->synchronizeUserActivity($user->id);
            } catch (Exception $e) {
                echo "Something went wrong while Redmine Task Sync ! \n";
                echo "Error message: " . $e->getMessage() . "\n";
                echo "Error trace: \n" . $e->getTraceAsString() . "\n\n";

                Log::error($e);
            }
        }
    }

    /**
     * Synchronize tasks for current user
     *
     * @param int $userId User's id in our system
     * @throws Exception
     */
    public function synchronizeUserNewTasks(int $userId)
    {
        $userNewRedmineTasks = $this->userRepo->getUserNewRedmineTasks($userId);

        $client = $this->clientFactory->createUserClient($userId);

        foreach ($userNewRedmineTasks as $task) {
            $redmineTask = $this->createRedmineTask($client, $task);
            $this->taskRepo->setRedmineId($task->id, (int) $redmineTask->id);
            $this->taskRepo->markAsOld($task->id);
        }
    }

    /**
     * Create task in Redmine
     *
     * @param  Redmine\Client  $client
     * @param                  $task
     *
     * @return SimpleXMLElement
     */
    public function createRedmineTask(Redmine\Client $client, $task)
    {
        // Get Redmine priority ID from an internal priority.
        $priority_id = 2;
        if (isset($task->priority_id) && $task->priority_id) {
            $priorities = $this->priority->getAll();
            $priority = array_first($priorities, function ($priority) use ($task) {
                return $priority['priority_id'] == $task->priority_id;
            });

            if (isset($priority)) {
                $priority_id = $priority['id'];
            }
        }

        return $client->issue->create(
            [
                'subject' => $task->task_name,
                'description' => $task->description,
                'project_id' => $this->projectRepo->getRedmineProjectId($task->project_id),
                'author_id' => $this->userRepo->getUserRedmineId($task->assigned_by),
                'assigned_to_id' => $this->userRepo->getUserRedmineId($task->user_id),
                'status_id' => 1,
                'priority_id' => $priority_id,
            ]
        );
    }

    /**
     * Synchronize task's for current user
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function synchronizeUserTasks(int $userId)
    {
        $client = $this->clientFactory->createUserClient($userId);
        //get current user's id
        $currentRedmineUser = $client->user->getCurrentUser() ?: [];
        $redmineUser = $currentRedmineUser['user'] ?? false;

        if (!$redmineUser) {
            // User have no redmine integration or wrong api key
            return;
        }

        $currentRedmineUserId = $redmineUser['id'];

        $statuses = $this->status->getAll();
        $activeStatuses = array_filter($statuses, function ($status) {
            return $status['is_active'] === true;
        });
        $activeStatusIDs = array_map(function ($status) {
            return $status['id'];
        }, $activeStatuses);

        $priorities = $this->priority->getAll();

        // Total user Tasks
        $tasksInfo = $client->issue->all([
            'limit' => 1,
            'assigned_to_id' => $currentRedmineUserId,
            'status_id' => '*'
        ]);

        $limit = 100;
        $totalTasks = $tasksInfo['total_count'];
        $chunkNums = (int) ceil($totalTasks / $limit);

        $synchronizedTasks = [];
        for ($chunkNum = 0; $chunkNum <= $chunkNums; $chunkNum++) {
            //get tasks assigned to current user
            $tasksData = $client->issue->all([
                'offset' => $limit * $chunkNum,
                'limit' => $limit,
                'assigned_to_id' => $currentRedmineUserId,
                'status_id' => '*'
            ]);

            $tasks = $tasksData['issues'] ?? [];

            foreach ($tasks as $taskFromRedmine) {
                $synchronizedTasks[] = $taskFromRedmine['id'];

                $taskExist = (new Property)->where([
                    ['entity_type', '=', Property::TASK_CODE],
                    ['name', '=', 'REDMINE_ID'],
                    ['value', '=', $taskFromRedmine['id']]
                ])->first();

                //if task already exists => check if task's properties is changed
                if ($taskExist != null) {
                    $task = Task::find($taskExist->entity_id);

                    // If we dont have a task by find method - task was soft deleted, need to delete it from Properties as well
                    if (!$task) {
                        $taskExist->forceDelete();
                        continue;
                    }

                    $this->maybeUpdateTask($userId, $taskFromRedmine, $priorities, $activeStatusIDs, $task);
                    continue;
                }

                $projectProperty = Property::where([
                    ['entity_type', '=', Property::PROJECT_CODE],
                    ['name', '=', 'REDMINE_ID'],
                    ['value', '=', $taskFromRedmine['project']['id']]
                ])->first();

                //if task's project exists in our system => add task
                if ($projectProperty && $projectProperty->entity_id) {
                    $this->createNewlySyncedTask($priorities, $taskFromRedmine, $projectProperty, $userId, $activeStatusIDs);
                }
            }

        }

        //check: if any task has been closed
       $this->checkClosedTasks($userId, $synchronizedTasks);
    }

    protected function checkClosedTasks(int $userId, array $redmineSynchronizedTasks)
    {
        $userLocalRedmineTasksIds = $this->userRepo->getUserRedmineTasks($userId);
        foreach ($userLocalRedmineTasksIds as $taskIds) {
            if (!in_array($taskIds->redmine_id, $redmineSynchronizedTasks)) {
                $localTask = Task::findOrFail($taskIds->task_id);

                if ($localTask && $localTask->active == 1) {
                    $localTask->active = 0;
                    $localTask->save();
                }
            }
        }
    }

    /**
     * Synchronize user task activity for userId
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function synchronizeUserActivity(int $userId)
    {
        $taskRepo = $this->taskRepo;
        $client = $this->clientFactory->createUserClient($userId);
        $activeStatusId = $this->status->getActiveStatusID();
        $inactiveStatusId = $this->status->getInactiveStatusID();
        $activateOnStatuses = $this->status->getActivateOnStatuses();
        $deactivateOnStatuses = $this->status->getDeactivateOnStatuses();
        $timeout = $this->settings->getOnlineTimeout();
        $timeActivity = $this->userTimeActivity($userId, $timeout);
        $unactiveTasks = $this->unactiveTasks($userId, $timeActivity);


        if ($activeStatusId && $timeActivity) {
            $activeTaskId = $timeActivity->task_id;
            $activeIssueId = $taskRepo->getRedmineTaskId($activeTaskId);
            $currentStatusId = $taskRepo->getRedmineStatusId($activeTaskId);
            if ($activeIssueId && $this->isInList($currentStatusId, $activateOnStatuses)) {
                if ($currentStatusId != $activeStatusId) {
                    $client->issue->update($activeIssueId, ['status_id' => $activeStatusId]);
                    $taskRepo->setRedmineStatusId($activeTaskId, $activeStatusId);
                }
            }
        }

        if ($inactiveStatusId) {
            foreach ($unactiveTasks as $task) { // is there any way to do it somehow else?
                $currentStatusId = $taskRepo->getRedmineStatusId($task->id);
                $issueId = $taskRepo->getRedmineTaskId($task->id);
                if ($issueId && $this->isInList($currentStatusId, $deactivateOnStatuses)) {
                    if ($currentStatusId != $inactiveStatusId) {
                        $client->issue->update($issueId, ['status_id' => $inactiveStatusId]);
                        $taskRepo->setRedmineStatusId($task->id, $inactiveStatusId);
                    }
                }
            }
        }
    }

    /**
     * get last user time activity
     *
     * @param string $userId
     *
     * @param $timeout
     * @return TimeActivity|null
     */
    protected function userTimeActivity(string $userId, $timeout)
    {

        $query = TimeActivity::where('user_id', $userId);

        if ($timeout) {
            $onlineTimestamp = time() /* now */ - $timeout; // timestamp when task counting as in progress
            $onlineDatetime = date('Y-m-d H:i:s', $onlineTimestamp);

            $query->where('last_time_activity', '>', $onlineDatetime);
        }

        return $query->first();
    }

    /**
     * get array of unactive tasks for $userId except for $timeActivity
     *
     * @param  string             $userId
     * @param  TimeActivity|null  $timeActivity
     *
     * @return Collection of Task
     */
    protected function unactiveTasks(string $userId, $timeActivity)
    {
        $unactiveTaskQuery = Task::query()
            ->where('user_id', $userId)
            ->where('active', true);


        if ($timeActivity) {
            $unactiveTaskQuery->where('id', '!=', $timeActivity->task_id);
        }

        return $unactiveTaskQuery->get();
    }

    /**
     * is status $redmineStatusId inside array $statusesList
     *
     * @param  string      $redmineStatusId
     * @param  array|null  $statusesList
     *
     * @return boolean
     */
    protected function isInList(string $redmineStatusId, $statusesList)
    {
        if (!$redmineStatusId) {
            return false;
        }

        if (!is_array($statusesList)) {
            return false;
        }

        return in_array($redmineStatusId, $statusesList);
    }

    private function createNewlySyncedTask(array $priorities, array $taskFromRedmine, Model $projectProperty, int $userId, array $activeStatusIDs)
    {
        // Get internal priority ID from a Redmine priority.
        $priority = array_first($priorities, function ($priority) use ($taskFromRedmine) {
            return $priority['id'] === $taskFromRedmine['priority']['id'];
        });
        $priority_id = isset($priority) ? $priority['priority_id'] : 0;

        $user_redmine_url = $this->userRepo->getUserRedmineUrl($userId);
        if (substr($user_redmine_url, -1) !== '/') {
            $user_redmine_url .= '/';
        }

        $taskInfo = [
            'project_id' => $projectProperty->entity_id,
            'task_name' => $taskFromRedmine['subject'],
            'description' => $taskFromRedmine['description'],
            'active' => in_array($taskFromRedmine['status']['id'], $activeStatusIDs),
            'user_id' => $userId,
            'assigned_by' => 1,
            'url' => $user_redmine_url . 'issues/' . $taskFromRedmine['id'],
            'priority_id' => $priority_id,
        ];

        $task = Task::create($taskInfo);

        //Add task redmine id property
        Property::create([
            'entity_id' => $task->id,
            'entity_type' => Property::TASK_CODE,
            'name' => 'REDMINE_ID',
            'value' => $taskFromRedmine['id']
        ]);
    }

    private function maybeUpdateTask(int $userId, array $taskFromRedmine, array $priorities, array $activeStatusIDs, Task $task)
    {
        $user_redmine_url = $this->userRepo->getUserRedmineUrl($userId);
        if (substr($user_redmine_url, -1) !== '/') {
            $user_redmine_url .= '/';
        }

        // Get internal priority ID from a Redmine priority.
        $priority = array_first($priorities, function ($priority) use ($taskFromRedmine) {
            return $priority['id'] === $taskFromRedmine['priority']['id'];
        });
        $priority_id = isset($priority) ? $priority['priority_id'] : 0;

        $data = [
            'task_name' => $taskFromRedmine['subject'] ?? '',
            'description' => $taskFromRedmine['description'] ?? '',
            'active' => in_array($taskFromRedmine['status']['id'], $activeStatusIDs),
            'user_id' => $userId,
            'url' => $user_redmine_url . 'issues/' . $taskFromRedmine['id'],
            'priority_id' => $priority_id,
        ];

        // Get project related to the task.
        $projectProperty = Property::where([
            ['entity_type', '=', Property::PROJECT_CODE],
            ['name', '=', 'REDMINE_ID'],
            ['value', '=', $taskFromRedmine['project']['id']]
        ])->first();

        // If project exists, update task project ID.
        if ($projectProperty && $projectProperty->entity_id) {
            $data['project_id'] = $projectProperty->entity_id;
        }

        $is_changed = $this->checkTaskFields($data, $task);

        $storedStatusId = $this->taskRepo->getRedmineStatusId($task->id);
        $redmineStatusId = $taskFromRedmine['status']['id'];

        if ($redmineStatusId != $storedStatusId) {
            $this->taskRepo->setRedmineStatusId($task->id, $redmineStatusId);
            $is_changed = true;
        }

        if ($is_changed) {
            $task->save();
        }
    }

    private function checkTaskFields(array $data, Task $task)
    {
        foreach ($data as $key => $value) {
            if ($task->$key !== $value) {
                $task->$key = $value;
                $changed = true;
            }
        }

        return $changed ?? false;
    }
}
