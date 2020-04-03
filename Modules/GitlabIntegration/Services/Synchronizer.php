<?php

namespace Modules\GitlabIntegration\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Log;
use Modules\GitlabIntegration\Entities\ProjectRelation;
use Modules\GitlabIntegration\Entities\TaskRelation;
use Modules\GitlabIntegration\Helpers\GitlabApi;

class Synchronizer
{
    public const COMPANY_ID = 'company_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const IMPORTANT = 'important';
    public const SOURCE = 'source';

    public const PROJECT_ID = 'project_id';
    public const TASK_NAME = 'task_name';
    public const ACTIVE = 'active';
    public const USER_ID = 'user_id';
    public const ASSIGNED_BY = 'assigned_by';
    public const URL = 'url';
    public const PRIORITY_ID = 'priority_id';

    public function synchronizeAll(): void
    {
        foreach (User::where('active', 1)->get() as $user) {
            $this->synchronize($user);
        }
    }

    public function synchronize(User $user): bool
    {
        $api = GitlabApi::buildFromUser($user);

        if (!$api) {
            Log::info('Can`t instantiate an API for user ' . $user->full_name . "\n");
            echo 'Can`t instantiate an API for user ' . $user->full_name . "\n";
            return false;
        }

        try {
            $gitlabProjects = $api->getUserProjects();
        } catch (\Throwable $throwable) {
            Log::error('Projects cant be fetched for user ' . $user->full_name . "\n");
            Log::error($throwable);
            echo 'Projects cant be fetched for user ' . $user->full_name . "\n";
            return false;
        }

        $this->syncProjects($gitlabProjects);

        try {
            $gitlabTasks = $api->getUserTasks();
        } catch (\Throwable $throwable) {
            Log::error('Tasks cant be fetched for user ' . $user->full_name . "\n");
            Log::error($throwable);
            echo 'Tasks cant be fetched for user ' . $user->full_name . "\n";
            return false;
        }

        $this->checkClosedTasks($api, $user->id);
        $this->syncTasks($gitlabTasks, $user->id);
        return true;
    }

    private function syncProjects(array $gitlabProjects): void
    {
        foreach ($gitlabProjects as $gitlabProject) {
            $projectMapping = [
                self::COMPANY_ID => 0,
                self::NAME => $gitlabProject['name'] ?? 'Gitlab Project without Name ?!',
                self::DESCRIPTION => $gitlabProject['description'] ?? '',
                self::IMPORTANT => false,
                self::SOURCE => 'gitlab',
            ];

            $relation = ProjectRelation::whereGitlabId($gitlabProject['id'])->first();
            if (!$relation) {
                $project = Project::create($projectMapping);
                ProjectRelation::create([
                    'gitlab_id' => $gitlabProject['id'],
                    'project_id' => $project->id,
                ]);
            } else {
                $project = Project::find($relation->project_id);

                // If project was deleted in our system we have to remove relation as well
                if (!$project) {
                    $relation->delete();
                    continue;
                }

                $project->name = $projectMapping[self::NAME];
                $project->description = $projectMapping[self::DESCRIPTION];
                $project->save();
            }
        }
    }

    private function checkClosedTasks(GitlabApi $api, int $userID): void
    {
        // Get Gitlab's iids of active user's tasks, to check if they were closed
        $relations = TaskRelation::whereHas('task', static function ($query) use ($userID) {
            $query->where('user_id', $userID);
            $query->where('active', true);
        })->get();
        $iids = $relations->pluck('gitlab_issue_iid')->toArray();

        // Fetch closed Gitlab's tasks by iids, and get internal task ids
        $gitlabTasks = $api->getClosedUserTasks($iids);
        $gitlabIds = array_map(static fn ($task) => (int)$task['id'], $gitlabTasks);
        $internalIds = $relations->whereIn('gitlab_id', $gitlabIds)->pluck('task_id')->toArray();

        if (!empty($internalIds)) {
            Task::whereIn('id', $internalIds)->update(['active' => false]);
        }
    }

    private function syncTasks(array $gitlabTasks, int $userID): void
    {
        foreach ($gitlabTasks as $gitlabTask) {
            $projectID = ProjectRelation::where('gitlab_id', $gitlabTask['project_id'])->first()->project_id;
            if (!$projectID) {
                Log::error("Project ID for gilab issue wasn`t found! {$gitlabTask['name'] }");
                echo "Project ID for gilab issue wasn`t found! {$gitlabTask['name'] } \n";
                continue;
            }

            $taskMapping = [
                self::TASK_NAME => $gitlabTask['title'] ?? 'Gitlab Issue without Name',
                self::DESCRIPTION => $gitlabTask['description'] ?? '',
                self::PROJECT_ID => $projectID,
                self::ACTIVE => true,
                self::ASSIGNED_BY => 0,
                self::URL => $gitlabTask['web_url'] ?? '',
                self::PRIORITY_ID => 2,
                self::IMPORTANT => false,
                self::USER_ID => $userID,
            ];

            $taskRelation = TaskRelation::find($gitlabTask['id']);

            if (!$taskRelation) {
                $task = Task::create($taskMapping);
                TaskRelation::create([
                    'gitlab_id' => $gitlabTask['id'],
                    'task_id' => $task->id,
                    'gitlab_issue_iid' => $gitlabTask['iid'],
                ]);
            } else {
                $task = Task::find($taskRelation->task_id);

                // If task was deleted in our system we have to remove relation as well
                if (!$task) {
                    $taskRelation->delete();
                    continue;
                }

                $taskRelation->gitlab_issue_iid = $gitlabTask['iid'];
                $taskRelation->save();

                $task->task_name = $taskMapping[self::TASK_NAME];
                $task->description = $taskMapping[self::DESCRIPTION];
                $task->user_id = $taskMapping[self::USER_ID];
                $task->active = true;
                $task->save();
            }
        }
    }
}
