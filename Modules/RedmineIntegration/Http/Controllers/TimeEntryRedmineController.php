<?php

namespace Modules\RedmineIntegration\Http\Controllers;


use App\Models\TimeInterval;

class TimeEntryRedmineController extends AbstractRedmineController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Gets time entry with id == $id from Redmine
     *
     * @param $id
     * @return \Illuminate\Http\Response|void
     */
    public function show($id)
    {
        dd($this->client->time_entry->show($id));
    }

    /**
     * Gets list of time entries
     */
    public function list()
    {
        dd($this->client->time_entry->all([
            'limit' => 1000
        ]));
    }

    public function create($timeIntervalId)
    {
        $timeInterval = TimeInterval::where('id', '=', $timeIntervalId)->first();
        $task = Task::where('id', '=', $timeInterval->task_id);

        $taskProperty = Property::where([
            ['entity_id', '=', $task->id],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        $timeIntervalInfo = [
            'issue_id'    => $taskProperty->value,
            'project_id'  => $task->project_id,
            'spent_on'    => null,
            'hours'       => null,
            'activity_id' => null,
            'comments'    => null,
        ];
    }

}
