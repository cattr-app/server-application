<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class EntityResolver
{
    protected $projects = [];
    protected $projectsInserted = [];
    protected $projectsRemoved = [];

    protected $tasks = [];
    protected $tasksInserted = [];
    protected $tasksRemoved = [];

    public function __construct()
    {
    }

    public function init(): void
    {
        $this->reloadProjects();
        $this->reloadTasks();
    }

    public function reloadProjects(): void
    {
        $this->projects = [];
        $this->projectsInserted = [];
        $this->projectsRemoved = [];

        $relations = \DB::table('gitlab_projects_relations')->get();

        $projects = Project::query()->whereIn('id', array_map(function ($relation) {
            return $relation->project_id;
        }, $relations->toArray()))->get();

        foreach ($relations as $relation) {
            $this->projects[$relation->gitlab_id] = $projects->where('id', $relation->project_id)->first();
        }
    }

    public function reloadTasks(): void
    {
        $this->tasks = [];
        $this->tasksInserted = [];
        $this->tasksRemoved = [];

        $relations = \DB::table('gitlab_tasks_relations')->get();

        $tasks = Task::query()->whereIn('id', array_map(function ($relation) {
            return $relation->task_id;
        }, $relations->toArray()))->get();

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
        if (!$this->hasGitlabProject($gitlabId) && !in_array($gitlabId, $this->projectsRemoved)) {
            $this->projectsInserted[] = [
                'gitlab_id' => $gitlabId,
                'project_id' => $project->id
            ];
            $this->projects[$gitlabId] = $project;
        }
    }

    public function insertTask(int $gitlabId, Task $task): void
    {
        if (!$this->hasGitlabTask($gitlabId) && !in_array($gitlabId, $this->tasksRemoved)) {
            $this->tasksInserted[] = [
                'gitlab_id' => $gitlabId,
                'task_id' => $task->id
            ];
            $this->tasks[$gitlabId] = $task;
        }
    }

    public function diffGitlabProjects(array $gitlabProjectsIds): array
    {
        return array_diff(array_keys($this->projects), $gitlabProjectsIds);
    }

    public function diffGitlabTasks(array $gitlabTasksIds): array
    {
        return array_diff(array_keys($this->tasks), $gitlabTasksIds);
    }

    public function removeProject(int $gitlabId): void
    {
        if ($this->hasGitlabProject($gitlabId)) {
            $this->projectsRemoved[] = $gitlabId;
            unset($this->projects[$gitlabId]);
        }
    }

    public function removeProjects(array $gitlabIds): void
    {
        foreach ($gitlabIds as $gitlabId) {
            $this->removeProject($gitlabId);
        }
    }

    public function removeTask(int $gitlabId): void
    {
        if ($this->hasGitlabTask($gitlabId)) {
            $this->tasksRemoved[] = $gitlabId;
            unset($this->tasks[$gitlabId]);
        }
    }

    public function removeTasks(array $gitlabIds): void
    {
        foreach ($gitlabIds as $gitlabId) {
            $this->removeTask($gitlabId);
        }
    }

    public function commit()
    {
        \DB::table('gitlab_projects_relations')->insert($this->projectsInserted);
        \DB::table('gitlab_tasks_relations')->insert($this->tasksInserted);

        \DB::table('gitlab_projects_relations')->whereIn('gitlab_id', $this->projectsRemoved)->delete();
        \DB::table('gitlab_tasks_relations')->whereIn('gitlab_id', $this->tasksRemoved)->delete();

        $this->projectsInserted = [];
        $this->projectsRemoved = [];

        $this->tasksInserted = [];
        $this->tasksRemoved = [];
    }
}
