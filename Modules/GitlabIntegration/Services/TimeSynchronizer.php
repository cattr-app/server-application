<?php


namespace Modules\GitlabIntegration\Services;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Modules\GitlabIntegration\Helpers\GitlabApi;
use Modules\GitlabIntegration\Helpers\TimeIntervalsHelper;

class TimeSynchronizer
{
    /**
     * @var GitlabApi
     */
    protected $api;

    /**
     * @var TimeIntervalsHelper
     */
    protected $timeIntervalsHelper;

    public function __construct(TimeIntervalsHelper $timeIntervalHelper)
    {
        $this->timeIntervalsHelper = $timeIntervalHelper;
    }

    public function synchronize()
    {
        $timeIntervals = $this->timeIntervalsHelper->getNotSyncedCollection();

        /** @var User $user */
        foreach (User::all() as $user) {
            $this->api = GitlabApi::buildFromUser($user);
            if (!$this->api) {
                Log::info("Can`t instantiate an API for user " . $user->full_name . "\n");
                continue;
            }

            $userIntervals = TimeInterval::whereIn('id', $timeIntervals->pluck('time_interval_id'))
                ->where('user_id', '=', $user->id)
                ->get();
            $groupedIntervals = $userIntervals->groupBy('task_id');

            $durations = $this->calculateDuration($groupedIntervals);
            $issueProjectRelations = $this->timeIntervalsHelper->getGitlabIssueProjectRelation(
                Task::whereIn('id',
                    $groupedIntervals->keys())->get()
            );

            foreach ($issueProjectRelations as $taskId => $relation) {
                $glProjectId = $relation['gl_project_id'];
                $glIssueIid = $relation['gl_issue_iid'];
                $response = $this->api->sendUserTime($glProjectId, $glIssueIid, $durations[$taskId]['humanDuration']);
                echo "Sending issue_iid "
                    . $glIssueIid
                    . " duration "
                    . $durations[$taskId]['humanDuration']
                    . " for user "
                    . $user->full_name
                    . "\n";

                if ($response && isset($response['total_time_spent'])) {
                    $this->timeIntervalsHelper->markAsSyncedIntervalByTaskId($taskId);
                }
            }

            $this->timeIntervalsHelper->clearSyncedIntervals();
        }

        return true;
    }

    /**
     * Method should return duration in human format f.e. "3h 30m"
     * @param Collection $groupedIntervals
     * @return array
     */
    private function calculateDuration(Collection $groupedIntervals)
    {
        $durations = [];
        foreach ($groupedIntervals as $taskId => $intervals) {
            if (!isset($durations[$taskId])) {
                $durations[$taskId]['duration'] = 0;
            }

            foreach ($intervals as $interval) {
                $durations[$taskId]['duration'] += Carbon::parse($interval->end_at)
                    ->diffInSeconds(Carbon::parse($interval->start_at));
            }

            // Set parts = 3 to see hours (if exists) minutes and seconds
            $durations[$taskId]['humanDuration'] = Carbon::now()
                ->subSeconds($durations[$taskId]['duration'])
                ->diffForHumans(null, true, true, 3);
        }

        return $durations;
    }
}
