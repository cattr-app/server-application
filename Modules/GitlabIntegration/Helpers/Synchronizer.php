<?php

namespace Modules\GitlabIntegration\Helpers;

use App\Models\Project;
use App\Models\Task;
use App\User;

class Synchronizer
{
    /**
     * @var EntityResolver
     */
    protected $entityResolver;

    public function __construct(EntityResolver $entityResolver)
    {
        $this->entityResolver = $entityResolver;
        // $this->entityResolver->init();
    }

    public function synchronize(User $user)
    {
        /** @var GitlabApi $api */
        $api = GitlabApi::buildFromUser($user);

        if (!$api) {
            return false;
        }

        $_userGitlabTasks = $api->getUserTasks();
        $userGitlabTasks = [];
        foreach ($_userGitlabTasks as $userTaskData) {
            if (!isset($userTaskData['project_id'])) {
                continue;
            }
            $projectId = $userTaskData['project_id'];
            if (!isset($userGitlabTasks[$projectId])) {
                $userGitlabTasks[$projectId] = [];
            }
            $userGitlabTasks[$projectId][] = $userTaskData;
        }

        $userGitlabProjects = $api->getUserProjects();
        $userGitlabProjectsIds = [];
        $userGitlabTasksIds = [];
        foreach ($userGitlabProjects as $gitlabProjectData) {
            $gitlabProjectId = (int)$gitlabProjectData['id'];

            $mapFromGitlabToProject = [
                'company_id' => 0,
                'name' => $gitlabProjectData['name'],
                'description' => $gitlabProjectData['description'],
                'important' => 0
            ];

            $projectCreated = false;
            $projectEdited = false;
            if (!($project = $this->entityResolver->getProjectByGitlabProject($gitlabProjectId))) {
                $project = new Project($mapFromGitlabToProject);
                $projectCreated = true;
            } else {
                // Don't touch this attributes:
                unset($mapFromGitlabToProject['company_id']);
                unset($mapFromGitlabToProject['important']);

                foreach ($mapFromGitlabToProject as $key => $value) {
                    if ($project->getAttribute($key) != $value) {
                        $project->setAttribute($key, $value);
                        $projectEdited = true;
                    }
                }
            }

            if (($projectCreated || $projectEdited) && !$project->save()) {
                continue;
            }

            if ($projectCreated) {
                $this->entityResolver->insertProject($gitlabProjectId, $project);
            }

            $userGitlabProjectsIds[] = $gitlabProjectId;

            echo "Project \"" . $project->name . "\" sync for " . $user->full_name . "\n";

            if (!isset($userGitlabTasks[$gitlabProjectId])) {
                continue;
            }

            foreach ($userGitlabTasks[$gitlabProjectId] as $gitlabTaskData) {
                $gitlabTaskId = (int)$gitlabTaskData['id'];

                $mapFromGitlabToTask = [
                    'project_id' => $project->id,
                    'task_name' => $gitlabTaskData['title'],
                    'description' => $gitlabTaskData['description'],
                    'active' => $gitlabTaskData['state'] != 'closed' ? 1 : 0,
                    'user_id' => $user->id,
                    'assigned_by' => 0,
                    'url' => $gitlabTaskData['web_url'],
                    'priority_id' => 2,
                    'important' => 0,
                ];

                $taskCreated = false;
                $taskEdited = false;
                if (!($task = $this->entityResolver->getTaskByGitlabTask($gitlabTaskId))) {
                    $task = new Task($mapFromGitlabToTask);
                    $taskCreated = true;
                } else {
                    // Don't touch this attributes:
                    unset($mapFromGitlabToTask['assigned_by']);
                    unset($mapFromGitlabToTask['priority_id']);
                    unset($mapFromGitlabToTask['important']);

                    foreach ($mapFromGitlabToTask as $key => $value) {
                        if ($task->getAttribute($key) != $value) {
                            $task->setAttribute($key, $value);
                            $taskEdited = true;
                        }
                    }
                }

                if (($taskCreated || $taskEdited) && !$task->save()) {
                    continue;
                }

                if ($taskCreated) {
                    $this->entityResolver->insertTask($gitlabTaskId, $task);
                }

                $userGitlabTasksIds[] = $gitlabTaskId;

                echo "Task \"" . $task->task_name . "\" attached to user " . $user->full_name . "\n";
            }
        }

        $diffProjectsIds = $this->entityResolver->diffGitlabProjects($userGitlabProjectsIds);
        foreach ($diffProjectsIds as $diffProjectId) {
            $project = $this->entityResolver->getProjectByGitlabProject($diffProjectId);
            if ($project) {
                try {
                    $project->delete();
                } catch (\Throwable $throwable) {
                }
            }
            $this->entityResolver->removeProject($diffProjectId);
        }

        $diffTasksIds = $this->entityResolver->diffGitlabTasks($userGitlabTasksIds);
        foreach ($diffTasksIds as $diffTaskId) {
            $task = $this->entityResolver->getTaskByGitlabTask($diffTaskId);
            if ($task) {
                try {
                    $task->delete();
                } catch (\Throwable $throwable) {
                }
            }
            $this->entityResolver->removeTask($diffProjectId);
        }

        $this->entityResolver->commit();

        return true;
    }

    public function synchronizeAll()
    {
        foreach (User::all() as $user) {
            $this->synchronize($user);
        }
    }


}
