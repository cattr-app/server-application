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
use Modules\RedmineIntegration\Models\Status;
use Symfony\Component\HttpFoundation\ParameterBag;

class PluginWebhookHelper extends AbstractPluginWebhookHelper
{
    protected Property $property;
    protected ProjectHelper $projectHelper;
    protected Status $status;

    public function __construct(
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        Request $request,
        Property $property,
        ProjectHelper $projectHelper,
        Status $status
    ) {
        parent::__construct($taskRepository, $projectRepository, $userRepository, $request);

        $this->property = $property;
        $this->projectHelper = $projectHelper;
        $this->status = $status;
    }

    /**
     * Process data saving from incoming request from plugin on redmine

     * @throws Exception
     */
    public function process(): Task
    {
        $taskData = $this->getTaskDataFromRequest();

        return $this->processTask($taskData);
    }

    /**
     * @param mixed|ParameterBag $task
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
     * @throws Exception
     */
    protected function metaInformationUpdate(): void
    {
        if (!$this->projectExists($this->getProjectDataFromRequest()['id'])) {
            $this->insertProject($this->getProjectDataFromRequest());
        }

        if (!$this->statusExists($this->getStatusDataFromRequest()['id'])) {
            $this->insertStatus($this->getStatusDataFromRequest());
        }

        if (!$this->priorityExists($this->getPriorityDataFromRequest()['name'])) {
            $this->insertPriority($this->getPriorityDataFromRequest());
        }
    }

    protected function projectExists(int $redmineProjectId): bool
    {
        $project = $this->property->getProperty(Property::PROJECT_CODE, 'REDMINE_ID', [
            'value' => $redmineProjectId
        ]);

        return (bool)$project->count();
    }

    /**
     * @param mixed|ParameterBag $project
     */
    protected function insertProject($project): void
    {
        $newProject = Project::create([
            'name' => $project['name'],
            'description' => $project['description'],
            'important' => 0,
            'source' => 'redmine',
        ]);

        Property::insert([
            'entity_id' => $newProject->id,
            'entity_type' => Property::PROJECT_CODE,
            'name' => 'REDMINE_ID',
            'value' => $project['id']
        ]);
    }

    protected function statusExists(int $redmineStatusId): bool
    {
        return $this->status->existsByID($redmineStatusId);
    }

    /**
     * @param mixed|ParameterBag $status
     *
     * @throws Exception
     */
    protected function insertStatus($status): void
    {
        $id = $status['id'] ?? 0;
        $name = $status['name'] ?? '';
        $active = !($status['is_closed'] ?? false);
        $closed = $status['is_closed'] ?? false;

        $this->status->add($id, $name, $active, $closed);
    }

    /**
     * @param $priorityName
     *
     * @return bool
     */
    protected function priorityExists($priorityName): bool
    {
        $data = Priority::where('name', $priorityName)->count();

        return (bool)$data;
    }

    /**
     * @param mixed|ParameterBag $priority
     */
    protected function insertPriority($priority): void
    {
        Priority::insert([
            'name' => $priority['name']
        ]);
    }

    protected function taskExists(int $redmineTaskId): bool
    {
        $task = $this->property->getProperty(Property::TASK_CODE, 'REDMINE_ID', [
            'value' => $redmineTaskId
        ]);

        return (bool)$task->count();
    }

    /**
     * @param mixed|ParameterBag $task
     *
     * @return Task
     * @throws Exception
     */
    public function addTask($task): Task
    {
        $assignedUser = $task['assigned_to_id'] ? $this->userRepository->getUserByRedmineId($task['assigned_to_id']) : null;
        $project = $this->projectHelper->getProjectByRedmineId($this->getProjectDataFromRequest()['id']);
        $author = $this->userRepository->getUserByRedmineId($task['author_id']);

        $taskAuthorId = $author ? $author->id : 1;
        $redmineUrl = Property::where([
            'entity_id' => $taskAuthorId,
            'entity_type' => Property::USER_CODE,
            'name' => 'REDMINE_URL'
        ])->first()->value;

        $taskUrl = "$redmineUrl/issues/" . $task['id'];

        $internalTask = Task::create([
            'project_id' => $project->id,
            'task_name' => $task['subject'],
            'description' => $task['description'],
            'active' => 1,
            'url' => $taskUrl,
            'user_id' => $taskAuthorId,
            'assigned_by' => $assignedUser ? $assignedUser->id : 1,
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
     * @param $priorityId
     *
     * @return mixed
     */
    protected function getInternalPriority($priorityId)
    {
        // TODO: Change to priority id, not name
        return Priority::where('name', $priorityId)->first();
    }

    /**
     * @param mixed|ParameterBag $task
     *
     * @todo
     * @deprecated
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
}
