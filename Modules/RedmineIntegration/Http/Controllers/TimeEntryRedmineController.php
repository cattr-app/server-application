<?php

namespace Modules\RedmineIntegration\Http\Controllers;

use App\Models\Property;
use App\Models\Task;
use App\Models\TimeInterval;
use DateTime;

class TimeEntryRedmineController extends AbstractRedmineController
{
    /**
     * TimeEntryRedmineController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns class name string
     *
     * @return string
     */
    public function getRedmineClientPropertyName()
    {
        return 'time_entry';
    }

    /**
     * Send Time Interval to Redmine
     *
     * Upload time interval with id == $timeIntercalId to Redmine by API
     *
     * @param $timeIntervalId
     */
    public function create($timeIntervalId)
    {
        $timeInterval = TimeInterval::where('id', '=', $timeIntervalId)->first();
        $task = Task::where('id', '=', $timeInterval->task_id)->first();

        //get task redmine id property
        $taskRedmineIdProperty = Property::where([
            ['entity_id', '=', $task->id],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        //get project redmin id property
        $projectRedmineIdProperty = Property::where([
            ['entity_id', '=', $task->project_id],
            ['entity_type', '=', Property::PROJECT_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        //calculate count of hours
        $startDateTime = new DateTime($timeInterval->start_at);
        $endDateTime = new DateTime($timeInterval->end_at);

        $diff = $endDateTime->diff($startDateTime);
        $diffHours = ($diff->days * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);

        $timeIntervalInfo = [
            'issue_id'    => $taskRedmineIdProperty->value,
            'project_id'  => $projectRedmineIdProperty->value,
            'spent_on'    => $startDateTime->format('Y-m-d'),
            'hours'       => round($diffHours, 2),
            'activity_id' => null,
            'comments'    => "Amazing Time Entry",
        ];

        $this->client->time_entry->create($timeIntervalInfo);
    }

}
