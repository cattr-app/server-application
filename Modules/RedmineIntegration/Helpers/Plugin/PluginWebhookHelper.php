<?php

namespace Modules\RedmineIntegration\Helpers\Plugin;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Property;
use App\Models\Task;
use Exception;
use Illuminate\Http\Request;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Helpers\ProjectHelper;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class PluginWebhookHelper
 *
 * @package Modules\RedmineIntegration\Helpers\Plugin
 */
class PluginWebhookHelper extends AbstractPluginWebhookHelper
{
    /**
     * @var Property
     */
    protected $property;
    /**
     * @var ProjectHelper
     */
    protected $projectHelper;

    /**
     * PluginWebhookHelper constructor.
     *
     * @param  TaskRepository     $taskRepository
     * @param  ProjectRepository  $projectRepository
     * @param  UserRepository     $userRepository
     * @param  Request            $request
     * @param  Property           $property
     * @param  ProjectHelper      $projectHelper
     */
    public function __construct(
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        Request $request,
        Property $property,
        ProjectHelper $projectHelper
    ) {
        parent::__construct($taskRepository, $projectRepository, $userRepository, $request);
        $this->property = $property;
        $this->projectHelper = $projectHelper;
    }

    /**
     * Process data saving from incoming request from plugin on redmine
     *
     * @return Task
     * @throws Exception
     */
    public function process(): Task
    {
        $taskData = $this->getTaskDataFromRequest();

        return $this->processTask($taskData);
    }

    /**
     * @param  mixed|ParameterBag  $task
     *
     * @return Task|mixed|ParameterBag|void
     * @throws Exception
     */
    protected function processTask($task)
    {
        $this->metaInformationUpdate($task);
        if (!$this->taskExists($task['id'])) {
            $task = $this->addTask($task);
        } else {
            $task = $this->updateTask($task);
        }

        return $task;
    }

    /**
     * @param  int  $redmineTaskId
     *
     * @return bool
     */
    protected function taskExists(int $redmineTaskId): bool
    {
        $task = $this->property->getProperty(Property::TASK_CODE, 'REDMINE_ID', [
            'value' => $redmineTaskId
        ]);

        return (bool) $task->count();
    }

    /**
     * @param  int  $redmineProjectId
     *
     * @return bool
     */
    protected function projectExists(int $redmineProjectId): bool
    {
        $project = $this->property->getProperty(Property::PROJECT_CODE, 'REDMINE_ID', [
            'value' => $redmineProjectId
        ]);

        return (bool) $project->count();
    }

    /**
     * TODO: Add int strict type to $redmineStatusId
     *
     * @param  int  $redmineStatusId
     *
     * @return bool
     */
    protected function statusExists($redmineStatusId): bool
    {
        $status = $this->property->getProperty(Property::USER_CODE, 'REDMINE_STATUSES')->first();

        // If there is no statuses at all we'll return false as long as we're checking for status existence, not
        // database filling
        if (!$status) {
            return false;
        }

        $status = unserialize($status->value);

        // TODO: Change condition to id comparision instead of name
        $statusExists = false;
        foreach ($status as $item) {
            if ($item['name'] == $redmineStatusId) {
                $statusExists = true;
                break;
            }
        }

        return $statusExists;
    }

    /**
     * @param  mixed|ParameterBag  $status
     *
     * @throws Exception
     */
    protected function insertStatus($status): void
    {
        $data = $this->property->getProperty(Property::USER_CODE, 'REDMINE_STATUSES')->first();

        if (!$data) {
            throw new Exception('Statuses are not synchronized');
        }

        $statuses = unserialize($data->value);

        $statuses[] = [
            'name' => $status['name'],
            'is_closed' => $status['is_closed'],
            'is_active' => 0
        ];

        $data->value = serialize($statuses);
        $data->save();
    }

    /**
     * @param  mixed|ParameterBag  $project
     */
    protected function insertProject($project): void
    {
        $newProject = Project::create([
            'name' => $project['name'],
            'description' => $project['description'],
            'important' => 0
        ]);

        Property::insert([
            'entity_id' => $newProject->id,
            'entity_type' => Property::PROJECT_CODE,
            'name' => 'REDMINE_ID',
            'value' => $project['id']
        ]);
    }

    /**
     * @param $priorityName
     *
     * @return bool
     */
    protected function priorityExists($priorityName): bool
    {
        $data = Priority::where('name', $priorityName)->count();

        return (bool) $data;
    }

    /**
     * @param  mixed|ParameterBag  $priority
     */
    protected function insertPriority($priority): void
    {
        Priority::insert([
            'name' => $priority['name']
        ]);
    }

    /**
     * @param $task
     *
     * @throws Exception
     */
    protected function metaInformationUpdate($task): void
    {
        if (!$this->projectExists($this->getProjectDataFromRequest()['id'])) {
            $this->insertProject($this->getProjectDataFromRequest());
        }

        if (!$this->statusExists($this->getStatusDataFromRequest()['name'])) {
            $this->insertStatus($this->getStatusDataFromRequest());
        }

        if (!$this->priorityExists($this->getPriorityDataFromRequest()['name'])) {
            $this->insertPriority($this->getPriorityDataFromRequest());
        }
    }

    /**
     * @param  mixed|ParameterBag  $task
     *
     * @return Task
     * @throws Exception
     */
    public function addTask($task): Task
    {
        $assignedUser = $this->userRepository->getUserByRedmineId($task['assigned_to_id']);
        $project = $this->projectHelper->getProjectByRedmineId($this->getProjectDataFromRequest()['id']);

        $internalTask = Task::create([
            'project_id' => $project->id,
            'task_name' => $task['subject'],
            'description' => $task['description'],
            'active' => 1,
            'user_id' => $assignedUser->id,
            'assigned_by' => $assignedUser->id,
            'priority_id' => $this->getInternalPriority($this->getPriorityDataFromRequest()['name'])->id,
        ]);

        Property::insert([
            'entity_id' => $internalTask->id,
            'entity_type' => Property::TASK_CODE,
            'name' => 'REDMINE_ID',
            'value' => $task['id'],
        ]);

        return $internalTask;
    }

    /**
     * @param mixed|ParameterBag $task
     *
     * @todo
     * @deprecated
     *
     */
    public function updateTask($task): void
    {
        $taskEav = Property::where([
            'entity_type' => Property::TASK_CODE,
            'name' => 'REDMINE_ID',
            'value' => $task['id']
        ])->first();
        $taskId = $taskEav->entity_id;

        $task = Task::find($taskId);
    }

    /**
     * @param $priorityId
     *
     * @return mixed
     */
    protected function getInternalPriority($priorityId)
    {
        // TODO: Change to priority id, not name
        $priority = Priority::where('name', $priorityId)->first();

        return $priority;
    }

}
