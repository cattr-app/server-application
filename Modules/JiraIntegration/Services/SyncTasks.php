<?php

namespace Modules\JiraIntegration\Services;

use App\Models\{Project, Task, User};
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\{Issue, IssueService};
use JiraRestApi\JiraException;
use JiraRestApi\Project\Project as JiraProject;
use JiraRestApi\Project\ProjectService;
use Modules\JiraIntegration\Entities\{ProjectRelation, Settings, TaskRelation};

class SyncTasks
{
    /** @var Settings */
    protected $settings;

    /** @var string */
    protected $host;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
        $this->host = $settings->getHost();
    }

    public function synchronizeAll()
    {
        if (empty($this->host) || !$this->settings->getEnabled()) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeAssignedIssues($user);
        }
    }

    public function synchronizeAssignedIssues(User $user)
    {
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($this->host) || empty($token)) {
            return;
        }

        $config = new ArrayConfiguration([
            'jiraHost'     => $this->host,
            'jiraUser'     => $user->email,
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $projectService = new ProjectService($config);

        try {
            $issues = $this->getAssignedIssues($issueService, $user);
            foreach ($issues as $issue) {
                $this->synchronizeIssue($projectService, $user, $issue);
            }
        } catch(JiraException $e) {
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

    protected function synchronizeIssue(ProjectService $projectService, User $user, Issue $issue)
    {
        $jiraProjectID = (int)$issue->fields->getProjectId();
        /** @var ProjectRelation $projectRelation */
        $projectRelation = ProjectRelation::find($jiraProjectID);
        if (!isset($projectRelation)) {
            $jiraProject = $projectService->get($jiraProjectID);
            $projectData = $this->toInternalProjectData($jiraProject);
            $project = Project::create($projectData);

            $projectRelation = ProjectRelation::create([
                'id'         => $jiraProjectID,
                'project_id' => $project->id,
            ]);
        } else {
            $project = $projectRelation->project;
            if (!isset($project)) {
                $jiraProject = $projectService->get($jiraProjectID);
                $projectData = $this->toInternalProjectData($jiraProject);
                $project = Project::create($projectData);

                $projectRelation->project_id = $project->id;
                $projectRelation->save();
            }
        }

        /** @var TaskRelation $taskRelation */
        $taskRelation = TaskRelation::find((int)$issue->id);
        if (!isset($taskRelation)) {
            $taskData = $this->toInternalTaskData($issue);
            $taskData['user_id'] = $user->id;
            $taskData['project_id'] = $projectRelation->project_id;
            $task = Task::create($taskData);

            TaskRelation::create([
                'id'      => (int)$issue->id,
                'task_id' => $task->id,
            ]);
        } else {
            $task = $taskRelation->task;
            if (!isset($task)) {
                $taskData = $this->toInternalTaskData($issue);
                $taskData['user_id'] = $user->id;
                $taskData['project_id'] = $projectRelation->project_id;
                $task = Task::create($taskData);

                $taskRelation->task_id = $task->id;
                $taskRelation->save();
            }
        }
    }

    protected function toInternalProjectData(JiraProject $project): array
    {
        return [
            'company_id'  => 0,
            'name'        => $project->name,
            'description' => $project->description,
            'important'   => false,
        ];
    }

    protected function toInternalTaskData(Issue $issue): array
    {
        return [
            'task_name'   => $issue->fields->summary,
            'description' => $issue->fields->description,
            'active'      => true,
            'assigned_by' => 0,
            'url'         => $issue->self,
            'created_at'  => $issue->fields->created,
            'updated_at'  => $issue->fields->updated,
            'priority_id' => 2,
            'important'   => false,
        ];
    }
}
