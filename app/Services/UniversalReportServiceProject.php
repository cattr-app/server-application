<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\UniversalReport;
use App\Models\User;
use Carbon\Carbon;
use DateTime;

class UniversalReportServiceProject
{
    private Carbon $startAt;
    private Carbon $endAt;
    private UniversalReport $report;
    private array $periodDates;

    public function __construct(Carbon $startAt, Carbon $endAt, UniversalReport $report, array $periodDates = [])
    {
        $this->startAt = $startAt;
        $this->endAt = $endAt;
        $this->report = $report;
        $this->periodDates = $periodDates;
    }
    public function getProjectReportData()
    {
        $projectFields = ['id'];
        foreach ($this->report->fields['projects'] as $field) {
            $projectFields[] = 'projects.' . $field;
        }
        $taskRelations = [];
        $taskFields = ['id','tasks.project_id'];
        foreach ($this->report->fields['main'] as $field) {
            if ($field !== 'priority' && $field !== 'status') {
                $taskFields[] = 'tasks.' . $field;
            } else {
                $taskRelations[] = $field;
                $taskFields[] = 'tasks.' . $field . '_id';
            }
        }
        $userFields = ['id'];
        foreach ($this->report->fields['users'] as $field) {
            $userFields[] = 'users.' . $field;
        }
        $project = Project::query()
            ->with(['tasks' => function ($query) use ($taskFields) {
                $query->select($taskFields);
            }, 'users' => function ($query) use ($userFields) {
                $query->select($userFields);
            }])
            ->select(array_merge($projectFields))->whereIn('id', $this->report->data_objects)->get();
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        dd($project);
    }

    public function fillNullDatesAsZeroTime(array &$datesToFill, $key = null)
    {
        foreach ($this->periodDates as $date) {
            if (is_null($key)) {
                unset($datesToFill['']);
                array_key_exists($date, $datesToFill) ? '' : $datesToFill[$date] = 0.0;
            } else {
                foreach ($datesToFill as $k => $item) {
                    unset($datesToFill[$k][$key]['']);

                    if(!array_key_exists($date, $item[$key])) {
                        $datesToFill[$k][$key][$date] = 0.0;
                    }
                    ksort($datesToFill[$k][$key]);
                }
            }
        }
    }
}
