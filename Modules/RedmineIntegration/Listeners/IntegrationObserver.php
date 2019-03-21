<?php

namespace Modules\RedmineIntegration\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use App\User;

/**
 * Class IntegrationObserver
 *
 * @property ProjectRepository $projectRepo
 * @property TaskRepository $taskRepo
 *
 * @package Modules\RedmineIntegration\Listeners
 */
class IntegrationObserver
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var ProjectRepository
     */
    public $projectRepo;

    /**
     * @var TaskRepository
     */
    public $taskRepo;

    /**
     * @var TaskRepository
     */
    public $userRepo;

    /**
     * Create the event listener.
     *
     * @param ProjectRepository $projectRepo
     * @param TaskRepository $taskRepo
     * @param UserRepository $userRepo
     */
    public function __construct(
        ProjectRepository $projectRepo,
        TaskRepository $taskRepo,
        UserRepository $userRepo
    ) {
        $this->projectRepo = $projectRepo;
        $this->taskRepo = $taskRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Observe task creation
     *
     * If task's project is Redmine project => mark task as NEW for synchronization
     *
     * @param $task
     * @return mixed
     */
    public function taskCreation($task)
    {
        $redmineProjectsIds = $this->projectRepo->getRedmineProjectsIds();

        if (in_array($task->project_id, $redmineProjectsIds)) {
            $this->taskRepo->markAsNew($task->id);
        }

        return $task;
    }


    /**
     * Observe user after edition
     *
     * @param $user
     * @return mixed
     */
    public function userAfterEdition($json)
    {

        $user = User::where('email', $json['res']->email)->first();

        $userId = $user->id;


        $sync = request()->input('redmine_sync');
        $redmine_active_status = request()->input('redmine_active_status');
        $redmine_deactive_status = request()->input('redmine_deactive_status');
        $redmine_ignore_statuses = request()->input('redmine_ignore_statuses');
        $sync  = $sync ? 1 : 0;

        /**
         * @todo: add rule, who can change that and who can not
         */
        $this->userRepo->setUserSendTime($userId, $sync);
        $this->userRepo->setActiveStatusId($userId, $redmine_active_status);
        $this->userRepo->setDeactiveStatusId($userId, $redmine_deactive_status);
        $this->userRepo->setIgnoreStatuses($userId, $redmine_ignore_statuses);


        $json['res']->redmine_sync = $sync;
        $json['res']->redmine_active_status = $redmine_active_status;
        $json['res']->redmine_deactive_status = $redmine_deactive_status;
        $json['res']->redmine_ignore_statuses = $redmine_ignore_statuses;
        return $json;
    }


    /**
     * Observe user show
     *
     * @param $user
     * @return mixed
     */
    public function userShow($user)
    {
        $user->redmine_sync = $this->userRepo->isUserSendTime($user->id);
        $user->redmine_active_status = $this->userRepo->getActiveStatusId($user->id);
        $user->redmine_deactive_status = $this->userRepo->getDeactiveStatusId($user->id);
        $user->redmine_ignore_statuses = $this->userRepo->getIgnoreStatuses($user->id);
        return $user;
    }


    /**
     * Observe task edition
     *
     * @param $task
     * @return mixed
     */
    public function taskEdition($task)
    {
        try {
            $userLocalRedmineTasksIds = $this->userRepo->getUserRedmineTasks($task->user_id);
            $redmineTask = array_first($userLocalRedmineTasksIds, function ($redmineTask) use ($task) {
                return $redmineTask->task_id === $task->id;
            });

            if (isset($redmineTask)) {
                // Get Redmine priority ID from an internal priority.
                $priority_id = 2;
                if (isset($task->priority_id) && $task->priority_id) {
                    $priorities = $this->userRepo->getUserRedminePriorities($task->user_id);
                    $priority = array_first($priorities, function ($priority) use ($task) {
                        return $priority['priority_id'] == $task->priority_id;
                    });

                    if (isset($priority)) {
                        $priority_id = $priority['id'];
                    }
                }

                $client = new RedmineClient($task->user_id);
                $client->issue->update($redmineTask->redmine_id, [
                    'priority_id' => $priority_id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Can't update task in the Redmine: " . $e->getMessage());
        }

        return $task;
    }

    public function rulesHook($rules)
    {
        $rules['redmine'] = [
            'statuses' => __('Get redmine avaliable statuses'),
        ];

        return $rules;
    }

}
