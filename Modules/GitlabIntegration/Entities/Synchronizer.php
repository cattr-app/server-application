<?php

namespace Modules\GitlabIntegration\Entities;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Log;
use Modules\GitlabIntegration\Helpers\EntityResolver;
use Modules\GitlabIntegration\Helpers\GitlabApi;

class Synchronizer
{
    const COMPANY_ID = 'company_id';
    const NAME = 'name';
    const DESCRIPTION = 'description';
    const IMPORTANT = 'important';

    const PROJECT_ID = 'project_id';
    const TASK_NAME = 'task_name';
    const ACTIVE = 'active';
    const USER_ID = 'user_id';
    const ASSIGNED_BY = 'assigned_by';
    const URL = 'url';
    const PRIORITY_ID = 'priority_id';

    /**
     * @var EntityResolver
     */
    protected $entityResolver;

    /**
     * @var array
     */
    protected $userGitlabTasksIds = [];

    /**
     * @var array
     */
    protected $userGitlabProjectsIds = [];

    /**
     * @var GitlabApi
     */
    protected $api = null;

    public function __construct(EntityResolver $entityResolver)
    {
        $this->entityResolver = $entityResolver;
    }

    public function synchronizeAll()
    {
        foreach (User::all() as $user) {
            $this->synchronize($user);
        }
    }

    public function synchronize(User $user)
    {
        if (!$this->initNewState($user)) {
            return false;
        }

        try {
            $_userGitlabTasks = $this->api->getUserTasks();
        } catch (\Throwable $throwable) {
            Log::error("Tasks cant be fetched for user " . $user->full_name . "\n");
            Log::error($throwable);

            return false;
        }

        $userGitlabTasks = $this->fillTasksByProjectsId($_userGitlabTasks);

        $userGitlabProjects = $this->api->getUserProjects();
        foreach ($userGitlabProjects as $gitlabProjectData) {

            $gitlabProjectId = (int)$gitlabProjectData['id'];

            $project = $this->fillProject($gitlabProjectData);
            if (!$project->save()) {
                Log::error(
                    "For some reason project "
                    . $project->name
                    . ' was not saved. User is '
                    . $user->full_name
                );

                continue;
            }

            // If we dont have in DB project -> insert new relation
            if (!$this->entityResolver->getProjectByGitlabProject($gitlabProjectId)) {
                $this->entityResolver->insertProject($gitlabProjectId, $project);
            }

            $this->userGitlabProjectsIds [] = $gitlabProjectId;
            Log::info("Project \"" . $project->name . "\" sync for " . $user->full_name . "\n");

            if (!isset($userGitlabTasks[$gitlabProjectId])) {
                continue;
            }

            foreach ($userGitlabTasks[$gitlabProjectId] as $gitlabTaskData) {
                $gitlabTaskId = (int)$gitlabTaskData['id'];
                $gitlabTaskIid = (int)$gitlabTaskData['iid'];

                $task = $this->fillTask($gitlabTaskData, $project, $user);
                if (!$task->save()) {
                    Log::error(
                        "For some reason project "
                        . $project->name
                        . ' was not saved. User is '
                        . $user->full_name
                    );

                    continue;
                }

                // If we dont have task in DB -> insert new relation
                if (!$this->entityResolver->getTaskByGitlabTask($gitlabTaskId)) {
                    $this->entityResolver->insertTask($gitlabTaskId, $task, $gitlabTaskIid);
                }

                $this->entityResolver->maybeUpdateTask($gitlabTaskId, $gitlabTaskIid);

                $this->userGitlabTasksIds [] = $gitlabTaskId;
                Log::info("Task \"" . $task->task_name . "\" attached to user " . $user->full_name . "\n");
            }
        }

        // Clean projects and tasks that was removed on gitlab
        $this->cleanDeleted();
        $this->entityResolver->commit();
        return true;
    }

    protected function fillTasksByProjectsId(array $gitlabTasks)
    {
        $result = [];
        foreach ($gitlabTasks as $taskData) {

            // This check was already here - do not know if Gitlab API can really send project w/o ID
            if (!isset($taskData['project_id'])) {
                continue;
            }

            $projectId = $taskData['project_id'];
            if (!isset($result[$projectId])) {
                $result[$projectId] = [];
            }

            $result[$projectId][] = $taskData;
        }

        return $result;
    }

    protected function fillProject(array $gitlabProjectData): Project
    {
        $projectId = (int)$gitlabProjectData['id'];

        $mappedProject = [
            self::COMPANY_ID => 0,
            self::NAME => $gitlabProjectData['name'],
            self::DESCRIPTION => $gitlabProjectData['description'],
            self::IMPORTANT => 0
        ];

        $project = $this->entityResolver->getProjectByGitlabProject($projectId);

        if (!$project) {
            $project = new Project($mappedProject);
        } else {
            // Don't touch this attributes:
            // Best comment ever...
            unset($mappedProject[static::COMPANY_ID]);
            unset($mappedProject[static::IMPORTANT]);

            foreach ($mappedProject as $key => $value) {
                if ($project->getAttribute($key) != $value) {
                    $project->setAttribute($key, $value);
                }
            }
        }

        return $project;
    }

    protected function fillTask($gitlabTaskData, Project $project, User $user): Task
    {
        $mapFromGitlabToTask = [
            self::PROJECT_ID => $project->id,
            self::TASK_NAME => $gitlabTaskData['title'],
            self::DESCRIPTION => $gitlabTaskData['description'],
            self::ACTIVE => $gitlabTaskData['state'] != 'closed' ? 1 : 0,
            self::USER_ID => $user->id,
            self::ASSIGNED_BY => 0,
            self::URL => $gitlabTaskData['web_url'],
            self::PRIORITY_ID => 2,
            self::IMPORTANT => 0,
        ];

        $task = $this->entityResolver->getTaskByGitlabTask($gitlabTaskData['id']);
        if (!$task) {
            $task = new Task($mapFromGitlabToTask);
        } else {
            // Relations for this attributes are not created yet
            unset($mapFromGitlabToTask['priority_id']);
            unset($mapFromGitlabToTask['important']);

            foreach ($mapFromGitlabToTask as $key => $value) {
                if ($task->getAttribute($key) != $value) {
                    $task->setAttribute($key, $value);
                }
            }
        }

        return $task;
    }

    protected function cleanDeleted()
    {
        $diffProjectsIds = $this->entityResolver->diffGitlabProjects($this->userGitlabProjectsIds);
        foreach ($diffProjectsIds as $diffProjectId) {
            $project = $this->entityResolver->getProjectByGitlabProject($diffProjectId);
            if ($project) {
                try {
                    $project->forceDelete();
                } catch (\Throwable $throwable) {
                    Log::error($throwable);
                }
            }
            $this->entityResolver->removeProject($diffProjectId);
        }

        $diffTasksIds = $this->entityResolver->diffGitlabTasks($this->userGitlabTasksIds);
        foreach ($diffTasksIds as $diffTaskId) {
            $task = $this->entityResolver->getTaskByGitlabTask($diffTaskId);
            if ($task) {
                try {
                    $task->forceDelete();
                } catch (\Throwable $throwable) {
                    Log::error($throwable);
                }
            }
            $this->entityResolver->removeTask($diffTaskId);
        }
    }

    protected function initNewState(User $user)
    {
        $this->api = GitlabApi::buildFromUser($user);

        if (!$this->api) {
            Log::info("Can`t instantiate an API for user " . $user->full_name . "\n");
            return false;
        }

        $this->entityResolver->init();

        $this->userGitlabProjectsIds = [];
        $this->userGitlabTasksIds = [];

        return true;
    }
}
