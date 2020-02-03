<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Project\Project as JiraProject;
use JiraRestApi\Project\ProjectService;

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
        if (empty($this->host)) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $this->synchronize($user);
        }
    }

    public function synchronize(User $user)
    {
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($this->host) || empty($token)) {
            return;
        }

        $config = new ArrayConfiguration([
            'jiraHost' => $this->host,
            'jiraUser' => $user->email,
            'jiraPassword' => $token,
        ]);

        $projectService = new ProjectService($config);
        $issueService = new IssueService($config);

        $take = 100;
        $result = $issueService->search('', 0, $take);
        $issues = $result->issues;
        $total = $result->total;

        for ($skip = $take; $skip < $total; $skip += $take) {
            $result = $issueService->search('', $skip, $take);
            $issues = array_merge($issues, $result->issues);
        }

        /** @var Issue[] $issues */
        foreach ($issues as $issue) {
            $jiraProjectID = (int)$issue->fields->getProjectId();
            $projectRelation = ProjectRelation::find($jiraProjectID);
            if (!isset($projectRelation)) {
                $jiraProject = $projectService->get($jiraProjectID);
                $projectData = $this->toInternalProjectData($jiraProject);
                $project = Project::create($projectData);

                $projectRelation = ProjectRelation::create([
                    'id' => $jiraProjectID,
                    'project_id' => $project->id,
                ]);
            }

            $taskRelation = TaskRelation::find((int)$issue->id);
            if (!isset($taskRelation)) {
                $taskData = $this->toInternalTaskData($issue);
                $taskData['user_id'] = $user->id;
                $taskData['project_id'] = $projectRelation->project_id;
                $task = Task::create($taskData);

                TaskRelation::create([
                    'id' => (int)$issue->id,
                    'task_id' => $task->id,
                ]);
            }
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
            'active' => true,
            'assigned_by' => 0,
            'url' => $issue->self,
            'created_at' => $issue->fields->created,
            'updated_at' => $issue->fields->updated,
            'priority_id' => 2,
            'important' => false,
        ];
    }
}
