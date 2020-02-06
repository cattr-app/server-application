<?php

namespace Modules\JiraIntegration\Services;

use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\Issue\Worklog;
use JiraRestApi\JiraException;
use Log;
use Modules\JiraIntegration\Entities\Settings;
use Modules\JiraIntegration\Entities\TaskRelation;
use Modules\JiraIntegration\Entities\TimeRelation;

class SyncTime
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

        $issueService = new IssueService($config);

        $timeRelations = TimeRelation::whereHas('timeInterval', function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        })->get();

        foreach ($timeRelations as $timeRelation) {
            /** @var TimeRelation $timeRelation */
            /** @var TaskRelation $taskRelation */
            $taskRelation = $timeRelation->taskRelation;
            $issueID = $taskRelation->id;

            /** @var TimeInterval $timeInterval */
            $timeInterval = $timeRelation->timeInterval;
            $startAt = Carbon::parse($timeInterval->start_at);
            $endAt = Carbon::parse($timeInterval->end_at);
            $duration = (int)$endAt->floatDiffInSeconds($startAt);

            $workLog = new Worklog();
            $workLog->setStartedDateTime($startAt)->setTimeSpentSeconds($duration);

            try {
                $issueService->addWorklog($issueID, $workLog);
                $timeRelation->delete();
            } catch (JiraException $e) {
                Log::error($e);
            }
        }
    }
}
