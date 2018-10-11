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

}
