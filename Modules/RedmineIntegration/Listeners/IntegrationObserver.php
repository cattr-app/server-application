<?php

namespace Modules\RedmineIntegration\Listeners;

use App\Models\Property;
use App\Models\User;
use Exception;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;
use Modules\RedmineIntegration\Models\Priority;

/**
 * Class IntegrationObserver
 *
 * @property ProjectRepository $projectRepo
 * @property TaskRepository    $taskRepo
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
     * @var UserRepository
     */
    public $userRepo;

    /**
     * @var ClientFactory
     */
    public $clientFactory;

    /**
     * @var Priority
     */
    public $priority;

    /**
     * Create the event listener.
     *
     * @param  ProjectRepository  $projectRepo
     * @param  TaskRepository     $taskRepo
     * @param  UserRepository     $userRepo
     * @param  ClientFactory      $clientFactory
     * @param  Priority           $priority
     */
    public function __construct(
        ProjectRepository $projectRepo,
        TaskRepository $taskRepo,
        UserRepository $userRepo,
        ClientFactory $clientFactory,
        Priority $priority
    ) {
        $this->projectRepo = $projectRepo;
        $this->taskRepo = $taskRepo;
        $this->userRepo = $userRepo;
        $this->clientFactory = $clientFactory;
        $this->priority = $priority;
    }

    /**
     * Observe task creation
     *
     * If task's project is Redmine project => mark task as NEW for synchronization
     *
     * @param $task
     *
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
     * @param $json
     *
     * @return mixed
     */
    public function userAfterEdition($json)
    {

        $user = User::where('email', $json['res']->email)->first();

        return $json;
    }

    /**
     * Observe user show
     *
     * @param $user
     *
     * @return mixed
     */
    public function userShow($user)
    {
        return $user;
    }

    /**
     * Observe task edition
     *
     * @param $task
     *
     * @return mixed
     */
    public function taskEdition($task)
    {
        if ($this->taskRepo->getRedmineTaskId($task->id)) {
            abort(403, 'Access denied to edit a task from Redmine integration');
        }

        return $task;
    }

    /**
     * Observe task list
     *
     * @param Collection|Paginator $tasks
     *
     * @return array
     */
    public function taskList($tasks)
    {
        if ($tasks instanceof Paginator) {
            $items = $tasks->getCollection();
        } else {
            $items = $tasks;
        }

        $taskIds = $items->map(function ($task) { return $task->id; })->toArray();
        $redmineTaskIds = Property::where([
            'entity_type' => Property::TASK_CODE,
            'name' => 'REDMINE_ID',
        ])->whereIn('entity_id', $taskIds)->pluck('entity_id')->toArray();

        $items->transform(function ($item) use ($redmineTaskIds) {
            if (in_array($item->id, $redmineTaskIds)) {
                $item->integration = 'redmine';
            }

            return $item;
        });

        if ($tasks instanceof Paginator) {
            $tasks->setCollection($items);
        } else {
            $tasks = $items;
        }

        return $tasks;
    }

    public function rulesHook($rules)
    {
        if (!isset($rules['integration'])) {
            $rules['integration'] = [];
        }

        $rules['integration']['redmine'] = __('Redmine integration');

        return $rules;
    }

}
