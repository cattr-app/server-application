<?php

namespace Modules\JiraIntegration\Entities;

use App\Models\User;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\Issue;
use JiraRestApi\Issue\IssueService;

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

        $issueService = new IssueService(new ArrayConfiguration([
            'jiraHost' => $this->host,
            'jiraUser' => $user->email,
            'jiraPassword' => $token,
        ]));

        $take = 100;
        $result = $issueService->search('', 0, $take);
        $issues = $result->issues;
        $total = $result->total;

        for ($skip = $take; $skip < $total; $skip += $take) {
            $result = $issueService->search('', $skip, $take);
            $issues = array_merge($issues, $result->issues);
        }

        $tasks = array_map([$this, 'toInternalTaskData'], $issues);

        foreach ($tasks as $task) {
            print_r($task);
        }
    }

    protected function toInternalTaskData(Issue $issue): array
    {
        return [
            'task_name' => $issue->fields->summary,
            'description' => $issue->fields->description,
            'active' => true,
            'user_id' => 0,
            'assigned_by' => 0,
            'url' => $issue->self,
            'created_at' => $issue->fields->created,
            'updated_at' => $issue->fields->updated,
            'priority_id' => 2,
            'important' => false,
        ];
    }
}
