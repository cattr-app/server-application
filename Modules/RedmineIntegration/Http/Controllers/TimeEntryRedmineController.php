<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Property;
use App\Models\Task;
use App\Models\TimeInterval;
use DateTime;

class TimeEntryRedmineController extends AbstractRedmineController
{
    /**
     * Send Time Interval to Redmine
     *
     * Upload time interval with id == $timeIntercalId to Redmine by API
     *
     * @param $timeIntervalId
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        $client = $this->initRedmineClient($user->id);

        $timeIntervalId = $request->time_interval_id;
        $timeInterval = TimeInterval::where('id', '=', $timeIntervalId)->first();
        $task = Task::where('id', '=', $timeInterval->task_id)->first();

        //calculate count of hours
        $startDateTime = new DateTime($timeInterval->start_at);
        $endDateTime = new DateTime($timeInterval->end_at);

        $diff = $endDateTime->diff($startDateTime);
        $diffHours = ($diff->days * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);

        $timeIntervalInfo = [
            'issue_id'    => $this->getRedmineTaskId($task->id),
            'project_id'  => $this->getRedmineProjectId($task->project_id),
            'spent_on'    => $startDateTime->format('Y-m-d'),
            'hours'       => round($diffHours, 2),
            'activity_id' => null,
            'comments'    => "Amazing Time Entry",
        ];

        $client->time_entry->create($timeIntervalInfo);
    }

    protected function getRedmineTaskId($taskId)
    {
        $taskRedmineIdProperty = Property::where([
            ['entity_id', '=', $taskId],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $taskRedmineIdProperty->value;
    }

    protected function getRedmineProjectId($projectId)
    {
        $projectRedmineIdProperty = Property::where([
            ['entity_id', '=', $projectId],
            ['entity_type', '=', Property::PROJECT_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $projectRedmineIdProperty->value;
    }

}
