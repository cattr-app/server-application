<?php

namespace Modules\JiraIntegration\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JiraRestApi\Project\Project as JiraProject;
use JiraRestApi\Project\ProjectService;
use Modules\JiraIntegration\Entities\ProjectRelation;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Entities\TaskRelation;

class SyncTasks
{
    /** @var Settings */
    protected $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function synchronizeAll(): void
    {
        $host = $this->settings->getHost();
        if (empty($host) || !$this->settings->getEnabled()) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeAssignedIssues($user);
        }
    }

    public function synchronizeAssignedIssues(User $user): void
    {
        $host = $this->settings->getHost();
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($host) || empty($token)) {
            return;
        }

        $config = new ArrayConfiguration([
            'jiraHost' => $host,
            'jiraUser' => $user->email,
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $projectService = new ProjectService($config);

        try {
            $issues = $this->getAssignedIssues($issueService, $user);
            foreach ($issues as $issue) {
                $this->synchronizeIssue($projectService, $user, $issue);
            }
        } catch (JiraException $e) {
            Log::error($e);
        }
    }

    /**
     * @param IssueService $issueService
     * @param User $user
     *
     * @return Issue[]
     */
    protected function getAssignedIssues(IssueService $issueService, User $user): array
    {
        $query = "assignee = \"{$user->email}\"";
        $take = 100;

        $result = $issueService->search($query, 0, $take);
        $issues = $result->issues;
        $total = $result->total;

        for ($skip = $take; $skip < $total; $skip += $take) {
            $result = $issueService->search($query, $skip, $take);
            $issues = array_merge($issues, $result->issues);
        }

        return $issues;
    }

    protected function synchronizeIssue(ProjectService $projectService, User $user, Issue $issue): void
    {
        $jiraProjectID = (int)$issue->fields->getProjectId();
        $jiraProject = $projectService->get($jiraProjectID);
        $projectData = $this->toInternalProjectData($jiraProject);

        /** @var ProjectRelation $projectRelation */
        $projectRelation = ProjectRelation::find($jiraProjectID);
        if (isset($projectRelation)) {
            $project = $projectRelation->project;
            if (isset($project)) {
                $project->fill($projectData);
                $project->save();
            } else {
                $project = Project::create($projectData);

                $projectRelation->project_id = $project->id;
                $projectRelation->save();
            }
        } else {
            $project = Project::create($projectData);

            $projectRelation = ProjectRelation::create([
                'id' => $jiraProjectID,
                'project_id' => $project->id,
            ]);
        }

        $taskData = $this->toInternalTaskData($issue);
        $taskData['user_id'] = $user->id;
        $taskData['project_id'] = $projectRelation->project_id;

        /** @var TaskRelation $taskRelation */
        $taskRelation = TaskRelation::find((int)$issue->id);
        if (isset($taskRelation)) {
            $task = $taskRelation->task;
            if (isset($task)) {
                $task->fill($taskData);
                $task->save();
            } else {
                $task = Task::create($taskData);

                $taskRelation->task_id = $task->id;
                $taskRelation->save();
            }
        } else {
            $task = Task::create($taskData);

            TaskRelation::create([
                'id' => (int)$issue->id,
                'task_id' => $task->id,
            ]);
        }
    }

    protected function toInternalProjectData(JiraProject $project): array
    {
        return [
            'company_id' => 0,
            'name' => $project->name,
            'description' => $project->description,
            'important' => false,
        ];
    }

    protected function toInternalTaskData(Issue $issue): array
    {
        return [
            'task_name' => $issue->fields->summary,
            'description' => $issue->fields->description,
            'active' => !isset($issue->fields->resolution),
            'assigned_by' => 0,
            'url' => $issue->self,
            'created_at' => $issue->fields->created,
            'updated_at' => $issue->fields->updated,
            'priority_id' => 2,
            'important' => false,
        ];
    }
}
