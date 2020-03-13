<?php

namespace Modules\JiraIntegration\Services;

use App\Models\{TimeInterval, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\{IssueService, Worklog};
use JiraRestApi\JiraException;
use Modules\JiraIntegration\Entities\{Settings, TaskRelation, TimeRelation};

class SyncTime
{
    /** @var Settings */
    protected $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function synchronizeAll()
    {
        $host = $this->settings->getHost();
        if (empty($host) || !$this->settings->getEnabled()) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $this->synchronizeUserTime($user);
        }
    }

    public function synchronizeUserTime(User $user)
    {
        $host = $this->settings->getHost();
        $token = $this->settings->getUserApiToken($user->id);
        if (empty($host) || empty($token)) {
            return;
        }

        $config = new ArrayConfiguration([
            'jiraHost'     => $host,
            'jiraUser'     => $user->email,
            'jiraPassword' => $token,
        ]);

        $issueService = new IssueService($config);
        $timeRelations = TimeRelation::where('user_id', $user->id)->get();
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
