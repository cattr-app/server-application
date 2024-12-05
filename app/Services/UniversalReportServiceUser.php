<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\UniversalReport;
use App\Models\User;
use Carbon\Carbon;

class UniversalReportServiceUser
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

    public function getUserReportData()
    {
        $projectFields = ['id'];
        foreach ($this->report->fields['projects'] as $field) {
            $projectFields[] = 'projects.' . $field;
        }
        $taskRelations = [];
        $taskFields = ['tasks.id', 'tasks.project_id'];
        foreach ($this->report->fields['tasks'] as $field) {
            if ($field !== 'priority' && $field !== 'status') {
                $taskFields[] = 'tasks.' . $field;
            } else {
                $taskRelations[] = 'tasks.' . $field;
                $taskFields[] = 'tasks.' . $field . '_id';
            }
        }
        $usersQuery = User::query()->with(['projects' => function ($query) use ($projectFields) {
            $query->select($projectFields);
        }])->with(['tasks' => function ($query) use ($taskFields) {
            $query->select($taskFields);
        }])->select(array_merge($this->report->fields['base'], ['id']))->whereIn('id', $this->report->data_objects);
        if (!empty($taskRelations)) $usersQuery = $usersQuery->with($taskRelations);
        $users = $usersQuery->get();
        foreach ($users as $user) {
            foreach ($user->projects as $project) {
                $project->tasks = $user->tasks->where('project_id', $project->id)->toArray();
            }
        }
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        $totalSpentTime = TimeInterval::whereIn('user_id', $users->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('user_id')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time')
            ->groupBy('user_id')
            ->pluck('total_spent_time', 'user_id');

        $totalSpentTimeDay = TimeInterval::whereIn('user_id', $users->pluck('id'))
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('user_id')
            ->selectRaw('DATE(start_at) as date_at')
            ->selectRaw(' SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_day')
            ->groupBy('user_id', 'date_at')->get()->toArray();
        foreach ($users as $user) {
            $worked_time_day = [];
            $startDateTime = Carbon::parse($this->startAt);
            $endDateTime = Carbon::parse($this->endAt);
            $user->total_spent_time = $totalSpentTime[$user->id] ?? 0;
            while ($startDateTime <= $endDateTime) {
                $currentDate = $startDateTime->format('Y-m-d');
                foreach ($totalSpentTimeDay as $item) {
                    if (($item['date_at'] === $currentDate) && ($user->id === (int)$item['user_id'])) {
                        $worked_time_day[$currentDate] = $item['total_spent_time_by_day'];
                        break;
                    }
                }
                if (!isset($worked_time_day[$currentDate])) {
                    $worked_time_day[$currentDate] = 0;
                }

                $startDateTime->modify('+1 day');
            }

            $user->worked_time_day = $worked_time_day;
            foreach ($user->projects as $project) {
                if (!empty($project['tasks'])) {
                    $tasks = $project['tasks'];
                    foreach ($tasks as $key => $task) {
                        if (isset($tasks[$key]['priority'])) {
                            $tasks[$key]['priority'] = $tasks[$key]['priority']['name'];
                        }
                        if (isset($tasks[$key]['status'])) {
                            $tasks[$key]['status'] = $tasks[$key]['status']['name'];
                        }
                    }
                    $project['tasks'] = $tasks;
                }
            }
        }
        return $users->keyBy('id');
    }
    public function getUserReportCharts()
    {
        $result = [];
        if (count($this->report->charts) === 0) {
            return $result;
        }
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_by_day = [
                'datasets' => [],
            ];
            $users = User::query()->whereIn('id', $this->report->data_objects)->get();
            $userNames = $users->pluck('full_name', 'id');
            TimeInterval::whereIn('user_id', $users->pluck('id'))
                ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
                ->select('user_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw(' SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_day')
                ->groupBy('user_id', 'date_at')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_by_day, $userNames) {
                    $time = sprintf("%02d.%02d", floor($timeInterval->total_spent_time_by_day / 3600), floor($timeInterval->total_spent_time_by_day / 60) % 60);
                    if (!array_key_exists($timeInterval->user_id, $total_spent_time_by_day['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_by_day['datasets'][$timeInterval->user_id] = [
                            'label' => $userNames[$timeInterval->user_id] ?? ' ',
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    }
                    $total_spent_time_by_day['datasets'][$timeInterval->user_id]['data'][$timeInterval->date_at] = $time;
                });
            $this->fillNullDatesAsZeroTime($total_spent_time_by_day['datasets'], 'data');

            $result['total_spent_time_day'] = $total_spent_time_by_day;
        }
        if (in_array('total_spent_time_day_and_tasks', $this->report->charts)) {
            $total_spent_time_by_day_and_tasks = [
                'datasets' => [],
            ];
            $userTasks = Task::whereHas('users', function ($query) {
                $query->whereIn('id', $this->report->data_objects);
            })->get();
            $taskNames = $userTasks->pluck('task_name', 'id');
            TimeInterval::whereIn('user_id', $this->report->data_objects)
                ->where('start_at', '>=',  $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=',  $endAt->format('Y-m-d H:i:s'))
                ->whereIn('task_id', $userTasks->pluck('id'))
                ->select('task_id', 'user_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day_and_tasks')
                ->groupBy('task_id', 'date_at', 'user_id')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_by_day_and_tasks,  $taskNames) {
                    $time = sprintf("%02d.%02d", floor($timeInterval->total_spent_time_by_day_and_tasks / 3600), floor($timeInterval->total_spent_time_by_day_and_tasks / 60) % 60);
                    if (!array_key_exists($timeInterval->user_id, $total_spent_time_by_day_and_tasks['datasets'])) {
                        $total_spent_time_by_day_and_tasks['datasets'][$timeInterval->user_id] = [];
                    }

                    if (!array_key_exists($timeInterval->task_id, $total_spent_time_by_day_and_tasks['datasets'][$timeInterval->user_id])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_by_day_and_tasks['datasets'][$timeInterval->user_id][$timeInterval->task_id] = [
                            'label' =>  $taskNames[$timeInterval->task_id] ?? ' ',
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    } else {
                        $total_spent_time_by_day_and_tasks['datasets'][$timeInterval->user_id][$timeInterval->task_id]['data'][$timeInterval->date_at] = $time;
                    }
                });

            foreach ($total_spent_time_by_day_and_tasks['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_by_day_and_tasks['datasets'][$key], 'data');
            }

            $result['total_spent_time_day_and_tasks'] = $total_spent_time_by_day_and_tasks;
        }
        if (in_array('total_spent_time_day_and_projects', $this->report->charts)) {
            $total_spent_time_by_day_and_projects = [
                'datasets' => [],
            ];
            $userProjects = Project::whereHas('users', function ($query) {
                $query->whereIn('id', $this->report->data_objects);
            })->get();
            $projectNames = $userProjects->pluck('name', 'id');
            $userTasks = Task::whereHas('users', function ($query) {
                $query->whereIn('id', $this->report->data_objects);
            })->pluck('project_id', 'id');

            $timeIntervals = TimeInterval::whereIn('user_id', $this->report->data_objects)
                ->where('start_at', '>=',  $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
                ->whereIn('task_id', $userTasks->keys())
                ->select('task_id', 'user_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day_and_projects')
                ->groupBy('task_id', 'date_at', 'user_id')
                ->get();
            $time = [];
            foreach ($timeIntervals as $timeInterval) {
                $projectId = $userTasks[$timeInterval->task_id];
                if (!isset($time[$timeInterval->date_at . '_' . $projectId . '_' . $timeInterval->user_id])) {
                    $time[$timeInterval->date_at . '_' . $projectId . '_' . $timeInterval->user_id] = 0;
                }
                $time[$timeInterval->date_at . '_' . $projectId . '_' . $timeInterval->user_id] += $timeInterval->total_spent_time_by_day_and_projects;
            }
            $timeIntervals->each(function ($timeInterval) use (&$total_spent_time_by_day_and_projects, $userTasks, $projectNames, $time) {
                if (!array_key_exists($timeInterval->user_id, $total_spent_time_by_day_and_projects['datasets'])) {
                    $total_spent_time_by_day_and_projects['datasets'][$timeInterval->user_id] = [];
                }
                $projectId = $userTasks[$timeInterval->task_id];
                if (!array_key_exists($projectId, $total_spent_time_by_day_and_projects['datasets'][$timeInterval->user_id])) {
                    $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                    $total_spent_time_by_day_and_projects['datasets'][$timeInterval->user_id][$projectId] = [
                        'label' =>  $projectNames[$projectId] ?? '',
                        'borderColor' => $color,
                        'backgroundColor' => $color,
                        'data' => [$timeInterval->date_at => $time[$timeInterval->date_at . '_' . $projectId . '_' . $timeInterval->user_id] / 3600],
                    ];
                }
                $total_spent_time_by_day_and_projects['datasets'][$timeInterval->user_id][$projectId]['data'][$timeInterval->date_at] = $time[$timeInterval->date_at . '_' . $projectId . '_' . $timeInterval->user_id] / 3600;
            });

            foreach ($total_spent_time_by_day_and_projects['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_by_day_and_projects['datasets'][$key], 'data');
            }
            $result['total_spent_time_day_and_projects'] = $total_spent_time_by_day_and_projects;
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
