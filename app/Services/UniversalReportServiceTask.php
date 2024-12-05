<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\UniversalReport;
use App\Models\User;
use Carbon\Carbon;

class UniversalReportServiceTask
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
    public function getTaskReportData()
    {
        $projectFields = ['id'];
        foreach ($this->report->fields['projects'] as $field) {
            $projectFields[] = 'projects.' . $field;
        }
        $taskRelations = [];
        $taskFields = ['id', 'tasks.project_id'];
        foreach ($this->report->fields['base'] as $field) {
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
        $tasksQuery = Task::query()
            ->with(['project' => function ($query) use ($projectFields) {
                $query->select($projectFields);
            }, 'users' => function ($query) use ($userFields) {
                $query->select($userFields);
            }])
            ->select(array_merge($taskFields))->whereIn('id', $this->report->data_objects);
        if (!empty($taskRelations)) $tasksQuery = $tasksQuery->with($taskRelations);
        $tasks = $tasksQuery->get();
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        $totalSpentTimeByUser = TimeInterval::whereIn('task_id', $tasks->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('user_id', 'task_id')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user')
            ->groupBy('user_id', 'task_id')
            ->get();
        $totalSpentTimeByUserAndDay = TimeInterval::whereIn('task_id', $tasks->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('user_id', 'task_id')
            ->selectRaw('DATE(start_at) as date_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_user_and_day')
            ->groupBy('user_id', 'date_at', 'task_id')->get();
        $totalSpentTime = TimeInterval::whereIn('task_id', $tasks->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('task_id')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time')
            ->groupBy('task_id')->pluck('total_spent_time', 'task_id');
        $totalSpentTimeByDay  = TimeInterval::whereIn('task_id', $tasks->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('task_id')
            ->selectRaw('DATE(start_at) as date_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_day')
            ->groupBy('task_id', 'date_at')->get();
        foreach ($tasks as $task) {
            $worked_time_day = [];
            $startDateTime = Carbon::parse($this->startAt);
            $endDateTime = Carbon::parse($this->endAt);
            $task->total_spent_time =  $totalSpentTime[$task->id] ?? 0;
            while ($startDateTime <= $endDateTime) {
                $currentDate = $startDateTime->format('Y-m-d');
                foreach ($totalSpentTimeByDay as $item) {
                    if (($item['date_at'] === $currentDate) && (int)$item['task_id'] === $task->id) {
                        $worked_time_day[$currentDate] = $item['total_spent_time_by_day'];
                        break;
                    }
                }
                if (!isset($worked_time_day[$currentDate])) {
                    $worked_time_day[$currentDate] = 0.0;
                }
                $startDateTime->modify('+1 day');
            }
            $task->worked_time_day =  $worked_time_day;
            foreach ($task->users as $user) {
                $worked_time_day = [];
                $startDateTime = Carbon::parse($this->startAt);
                $endDateTime = Carbon::parse($this->endAt);
                $user->total_spent_time_by_user =  $totalSpentTimeByUser->where('user_id', $user->id)->where('task_id', $task->id)->first()->total_spent_time_by_user ?? 0;
                while ($startDateTime <= $endDateTime) {
                    $currentDate = $startDateTime->format('Y-m-d');
                    foreach ($totalSpentTimeByUserAndDay as $item) {
                        if (($item['date_at'] === $currentDate) && ($user->id === (int)$item['user_id'] && (int)$item['task_id'] === $task->id)) {
                            $worked_time_day[$currentDate] = $item['total_spent_time_by_user_and_day'];
                            break;
                        }
                    }
                    if (!isset($worked_time_day[$currentDate])) {
                        $worked_time_day[$currentDate] = 0.0;
                    }
                    $startDateTime->modify('+1 day');
                }
                $user->workers_day = $worked_time_day;
            }
        }
        $tasks = $tasks->keyBy('id')->toArray();
        foreach ($tasks as &$task) {
            if (isset($task['priority'])) $task['priority'] = $task['priority']['name'];
            if (isset($task['status'])) $task['status'] = $task['status']['name'];
        }
        return  $tasks;
    }

    public function getTasksReportCharts()
    {
        $result = [];
        if (count($this->report->charts) === 0) {
            return $result;
        }
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        $tasks = Task::whereIn('id', $this->report->data_objects)->get();
        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_day = [
                'datasets' => []
            ];
            $taskNames = $tasks->pluck('task_name', 'id');
            TimeInterval::whereIn('task_id', $this->report->data_objects)
                ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
                ->select('task_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_day')
                ->groupBy('task_id', 'date_at')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_day, $taskNames) {
                    $time = sprintf("%02d.%02d", floor($timeInterval->total_spent_time_day / 3600), floor($timeInterval->total_spent_time_day / 60) % 60);
                    if (!array_key_exists($timeInterval->task_id, $total_spent_time_day['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_day['datasets'][$timeInterval->task_id] = [
                            'label' => $taskNames[$timeInterval->task_id] ?? ' ',
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    }
                    $total_spent_time_day['datasets'][$timeInterval->task_id]['data'][$timeInterval->date_at] = $time;
                });
            $this->fillNullDatesAsZeroTime($total_spent_time_day['datasets'], 'data');
            $result['total_spent_time_day'] = $total_spent_time_day;
        }

        if (in_array('total_spent_time_day_users_separately', $this->report->charts)) {
            $total_spent_time_day_users_separately = [
                'datasets' => [],
            ];
            $userTasks = User::whereHas('tasks', function ($query) {
                $query->whereIn('task_id', $this->report->data_objects);
            })->get();
            $userNames = $userTasks->pluck('full_name', 'id');
            TimeInterval::whereIn('task_id', $this->report->data_objects)
                ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=',  $endAt->format('Y-m-d H:i:s'))
                ->select('task_id', 'user_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_day_users_separately')
                ->groupBy('task_id', 'date_at', 'user_id')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_day_users_separately, $userNames) {
                    $time = $timeInterval->total_spent_time_day_users_separately;
                    $taskId = $timeInterval->task_id;
                    $userId = $timeInterval->user_id;
                    if (!isset($total_spent_time_day_users_separately['datasets'][$taskId])) {
                        $total_spent_time_day_users_separately['datasets'][$taskId] = [];
                    }
                    if (!isset($total_spent_time_day_users_separately['datasets'][$taskId][$userId])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_day_users_separately['datasets'][$taskId][$userId] = [
                            'label' =>  $userNames[$userId] ?? ' ',
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    }
                    $total_spent_time_day_users_separately['datasets'][$timeInterval->task_id][$timeInterval->user_id]['data'][$timeInterval->date_at] = $time;
                });
            foreach ($total_spent_time_day_users_separately['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_day_users_separately['datasets'][$key], 'data');
            }

            $result['total_spent_time_day_users_separately'] = $total_spent_time_day_users_separately;
        }
        return $result;
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
                    if (!array_key_exists($date, $item[$key])) {
                        $datesToFill[$k][$key][$date] = 0.0;
                    }
                    ksort($datesToFill[$k][$key]);
                }
            }
        }
    }
}
