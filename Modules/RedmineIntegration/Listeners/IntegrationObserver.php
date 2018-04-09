<?php

namespace Modules\RedmineIntegration\Listeners;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;

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
     * Create the event listener.
     *
     * @param ProjectRepository $projectRepo
     * @param TaskRepository $taskRepo
     */
    public function __construct(ProjectRepository $projectRepo, TaskRepository $taskRepo)
    {
        $this->projectRepo = $projectRepo;
        $this->taskRepo = $taskRepo;
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


}
