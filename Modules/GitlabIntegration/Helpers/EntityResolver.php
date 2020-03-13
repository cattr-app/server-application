<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class EntityResolver
 */
class EntityResolver
{
    const PROJECTS_TABLE = 'gitlab_projects_relations';
    const TASKS_TABLE = 'gitlab_tasks_relations';

    protected $projects = [];
    protected $projectsToInsert = [];
    protected $projectsToRemove = [];

    protected $tasks = [];
    protected $tasksToInsert = [];
    protected $tasksToDelete = [];
    protected $tasksToUpdate = [];

    /**
     * @var $relations Collection
     */
    protected $taskRelations;

    public function init(): void
    {
        $this->loadProjects();
        $this->loadTasks();
    }

    // Fetch relation Projects -> GitProjects
    public function loadProjects(): void
    {
        $this->clearProjects();

        $relations = \DB::table('gitlab_projects_relations')->get();
        $projects = Project::whereIn('id', $relations->pluck('project_id'))->get();

        foreach ($relations as $relation) {
            $this->projects[$relation->gitlab_id] = $projects->where('id', $relation->project_id)->first();
        }
    }

    // Fetch relation Tasks -> GitTasks
    public function loadTasks(): void
    {
        $this->clearTasks();

        $relations = \DB::table('gitlab_tasks_relations')->get();
        $this->taskRelations = $relations;
        $tasks = Task::query()->whereIn('id', $relations->pluck('task_id'))->get();

        foreach ($relations as $relation) {
            $this->tasks[$relation->gitlab_id] = $tasks->where('id', $relation->task_id)->first();
        }
    }

    public function hasGitlabProject(int $gitlabProjectId): bool
    {
        return isset($this->projects[$gitlabProjectId]);
    }

    public function hasGitlabTask(int $gitlabTaskId): bool
    {
        return isset($this->tasks[$gitlabTaskId]);
    }

    public function getProjectByGitlabProject(int $gitlabProjectId): ?Project
    {
        if (!$this->hasGitlabProject($gitlabProjectId)) {
            return null;
        }
        return $this->projects[$gitlabProjectId];
    }

    public function getTaskByGitlabTask(int $gitlabTaskId): ?Task
    {
        if (!$this->hasGitlabTask($gitlabTaskId)) {
            return null;
        }
        return $this->tasks[$gitlabTaskId];
    }

    public function insertProject(int $gitlabId, Project $project): void
    {
        if (!$this->hasGitlabProject($gitlabId) && !in_array($gitlabId, $this->projectsToRemove)) {
            $this->projectsToInsert [] = [
                'gitlab_id' => $gitlabId,
                'project_id' => $project->id
            ];
            $this->projects[$gitlabId] = $project;
        }
    }

    public function insertTask(int $gitlabId, Task $task, int $gitlabTaskIid): void
    {
        if (!$this->hasGitlabTask($gitlabId) && !in_array($gitlabId, $this->tasksToDelete)) {
            $this->tasksToInsert [] = [
                'gitlab_id' => $gitlabId,
                'task_id' => $task->id,
                'gitlab_issue_iid' => $gitlabTaskIid // Need this field when run TimeSync
            ];
            $this->tasks[$gitlabId] = $task;
        }
    }

    public function maybeUpdateTask(int $gitlabId, int $gitlabIid)
    {
        $taskRelations = $this->taskRelations->where(
            'gitlab_issue_iid',
            '=',
            0
        )->pluck('gitlab_id')
            ->toArray();

        if (in_array($gitlabId, $taskRelations)) {
            $this->tasksToUpdate [] = [
                'gitlab_id' => $gitlabId,
                'gitlab_issue_iid' => $gitlabIid
            ];
        }
    }

    // difference between userProjects and our DB projects
    public function diffGitlabProjects(array $gitlabProjectsIds): array
    {
        return array_diff(array_keys($this->projects), $gitlabProjectsIds);
    }

    // difference between userTasks and our DB tasks
    public function diffGitlabTasks(array $gitlabTasksIds): array
    {
        return array_diff(array_keys($this->tasks), $gitlabTasksIds);
    }

    public function removeProject(int $gitlabId): void
    {
        if ($this->hasGitlabProject($gitlabId)) {
            $this->projectsToRemove [] = $gitlabId;
            unset($this->projects[$gitlabId]);
        }
    }

    public function removeTask(int $gitlabId): void
    {
        if ($this->hasGitlabTask($gitlabId)) {
            $this->tasksToDelete [] = $gitlabId;
            unset($this->tasks[$gitlabId]);
        }
    }

    public function commit()
    {
        \DB::table(self::PROJECTS_TABLE)->insert($this->projectsToInsert);
        \DB::table(self::TASKS_TABLE)->insert($this->tasksToInsert);

        foreach ($this->tasksToUpdate as $task) {
            \DB::table(self::TASKS_TABLE)->where('gitlab_id', '=', $task['gitlab_id'])->update([
                'gitlab_issue_iid' => $task['gitlab_issue_iid']
            ]);
        }

        \DB::table(self::PROJECTS_TABLE)->whereIn('gitlab_id', $this->projectsToRemove)->delete();
        \DB::table(self::TASKS_TABLE)->whereIn('gitlab_id', $this->tasksToDelete)->delete();

        $this->clearTasks();
        $this->clearProjects();
    }

    private function clearTasks()
    {
        $this->tasks = [];
        $this->tasksToInsert = [];
        $this->tasksToDelete = [];
        $this->taskRelations = [];
        $this->tasksToUpdate = [];
    }

    private function clearProjects()
    {
        $this->projects = [];
        $this->projectsToInsert = [];
        $this->projectsToRemove = [];
    }

    public static function getGitlabIdByProjectId(int $projectId)
    {
        return DB::table(self::PROJECTS_TABLE)->where(
            'project_id',
            '=',
            $projectId
        )->first(['gitlab_id']);
    }
}
