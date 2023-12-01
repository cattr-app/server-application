<?php

namespace App\Services;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\UniversalReport;
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
        foreach ($this->report->fields['main'] as $field) {
            $projectFields[] = 'projects.' . $field;
        }
        $taskRelations = [];
        $taskFields = ['id', 'tasks.project_id'];
        foreach ($this->report->fields['tasks'] as $field) {
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
        $projects = Project::query()
            ->with(['tasks' => function ($query) use ($taskFields) {
                $query->select($taskFields);
            }, 'users' => function ($query) use ($userFields) {
                $query->select($userFields);
            }])
            ->select(array_merge($projectFields))->whereIn('id', $this->report->data_objects)->get();
        $usersId = [];
        $tasksId = [];
        foreach ($projects as $project) {
            $usersId[] = $project->users->pluck('id');
            $tasksId[] = $project->tasks->pluck('id');
        }
        $taskQuery = Task::whereIn('project_id', $projects->pluck('id'));
        $projectIdsIndexedByTaskIds = $taskQuery->pluck('project_id', 'id');
        $usersId = collect($usersId)->flatten()->toArray();
        $tasksId = collect($tasksId)->flatten()->toArray();
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        $totalSpentTimeByUserAndDay = TimeInterval::whereIn('user_id', $usersId)
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('user_id', 'task_id')
            ->selectRaw('DATE(start_at) as date_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_user_and_day')
            ->groupBy('user_id', 'date_at', 'task_id')->get();
        $totalSpentTimeByDay = TimeInterval::whereIn('task_id', $tasksId)
            ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
            ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
            ->select('task_id')
            ->selectRaw('DATE(start_at) as date_at')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_by_day')
            ->groupBy('date_at', 'task_id')->get();
        foreach ($projects as $project) {
            foreach ($project->users as $user) {
                $worked_time_day = [];
                $totalSpentTimeUser = 0;
                $startDateTime = new DateTime($this->startAt);
                $endDateTime = new DateTime($this->endAt);
                while ($startDateTime <= $endDateTime) {
                    $currentDate = $startDateTime->format('Y-m-d');
                    foreach ($totalSpentTimeByUserAndDay as $timeInterval) {
                        $worked_time_day[$currentDate] = 0;
                        $projectId = (int)$timeInterval->task->project_id;
                        foreach ($projectIdsIndexedByTaskIds as $taskId => $id) {
                            if (($timeInterval['date_at'] === $currentDate) && ($user->id === (int)$timeInterval->user_id && $projectId === $project->id)) {
                                $worked_time_day[$currentDate] += $timeInterval['total_spent_time_by_user_and_day'];
                                $totalSpentTimeUser += $timeInterval['total_spent_time_by_user_and_day'];
                                break;
                            }
                        }
                    }

                    if (!isset($worked_time_day[$currentDate])) {
                        $worked_time_day[$currentDate] = 0.0;
                    }
                    $startDateTime->modify('+1 day');
                }

                foreach ($project->tasks as $task) {

                    $task->priority = Priority::find($task->priority_id)->name ?? 'Unknown';
                    $task->status = Status::find($task->status_id)->name ?? 'Unknown';
                }
                $user->workers_day = $worked_time_day;
                $user->total_spent_time_by_user = $totalSpentTimeUser;
            }
            $worked_time_day = [];
            $startDateTime = new DateTime($this->startAt);
            $endDateTime = new DateTime($this->endAt);
            while ($startDateTime <= $endDateTime) {
                $currentDate = $startDateTime->format('Y-m-d');
                foreach ($totalSpentTimeByDay as $timeInterval) {
                    $time = 0;
                    $projectId = (int)$timeInterval->task->project_id;
                    foreach ($projectIdsIndexedByTaskIds as $taskId => $id) {
                        if ($projectId === $id) {
                            $time += $timeInterval->total_spent_time_by_day;
                        }
                    }
                    if ($timeInterval['date_at'] === $currentDate && $projectId  === $project->id) {
                        $worked_time_day[$currentDate] =  $time;
                        break;
                    }
                }
                if (!isset($worked_time_day[$currentDate])) {
                    $worked_time_day[$currentDate] = 0.0;
                }
                $startDateTime->modify('+1 day');
            }
            $project->worked_time_day =  $worked_time_day;
        }
        $projects = $projects->toArray();
        foreach ($projects as &$project) {
            $createdAt = new DateTime(isset($project['created_at']) ? $project['created_at'] : 0);
            $project['created_at'] = $createdAt->format('Y-m-d H:i:s');
        }
        unset($project);
        return $projects;
    }
    public function getProjectReportCharts()
    {
        $result = [];

        if (count($this->report->charts) === 0) {
            return $result;
        }
        $projects = Project::query()
            ->with(['tasks', 'users'])
            ->whereIn('id', $this->report->data_objects)->get();
        $usersId = [];
        $tasksId = [];
        foreach ($projects as $project) {
            $projectsName = $project->pluck('name', 'id');
            $usersId[] = $project->users->pluck('id');
            $tasksId[] = $project->tasks->pluck('id');
            foreach ($project->users as $user) {
                $userNames = $user->pluck('full_name', 'id');
            }
        }
        $endAt = clone $this->endAt;
        $endAt = $endAt->endOfDay();
        $usersId = collect($usersId)->flatten()->toArray();
        $tasksId = collect($tasksId)->flatten()->toArray();
        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_day = [
                'datasets' => []
            ];
            $taskQuery = Task::whereIn('project_id', $projects->pluck('id'));
            $projectIdsIndexedByTaskIds = $taskQuery->pluck('project_id', 'id');
            TimeInterval::whereIn('task_id', $tasksId)
                ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
                ->select('task_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_day')
                ->groupBy('date_at', 'task_id')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_day, $userNames, $projectIdsIndexedByTaskIds, $projectsName) {
                    $time = 0;
                    $projectId = (int)$timeInterval->task->project_id;

                    foreach ($projectIdsIndexedByTaskIds as $taskId => $id) {
                        if ($projectId === $id) {
                            $time += $timeInterval->total_spent_time_day;
                        }
                    }
                    if (!isset($total_spent_time_day['datasets'][$projectId])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_day['datasets'][$projectId] = [
                            'label' => $projectsName[$projectId],
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    }
                    $total_spent_time_day['datasets'][$projectId]['data'][$timeInterval->date_at] = $time;
                });

            foreach ($total_spent_time_day['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_day['datasets'], 'data');
            }
            $result['total_spent_time_day'] = $total_spent_time_day;
        }
        if (in_array('total_spent_time_day_and_users_separately', $this->report->charts)) {
            $total_spent_time_day_and_users_separately = [
                'datasets' => [],
            ];

            TimeInterval::whereIn('user_id', $usersId)
                ->where('start_at', '>=', $this->startAt->format('Y-m-d H:i:s'))
                ->where('end_at', '<=', $endAt->format('Y-m-d H:i:s'))
                ->select('user_id', 'task_id')
                ->selectRaw('DATE(start_at) as date_at')
                ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at))  as total_spent_time_day_and_users_separately')
                ->groupBy('user_id', 'date_at', 'task_id')
                ->get()
                ->each(function ($timeInterval) use (&$total_spent_time_day_and_users_separately, $userNames) {
                    $time = $timeInterval->total_spent_time_day_and_users_separately;
                    $projectId = (int)$timeInterval->task->project_id;
                    if (!isset($total_spent_time_day_and_users_separately['datasets'][$projectId])) {
                        $total_spent_time_day_and_users_separately['datasets'][$projectId] = [];
                    }
                    $userId = $timeInterval->user_id;
                    if (!isset($total_spent_time_day_and_users_separately['datasets'][$projectId][$userId])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        $total_spent_time_day_and_users_separately['datasets'][$projectId][$userId] = [
                            'label' => $userNames[$timeInterval->user_id] ?? ' ',
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$timeInterval->date_at => $time],
                        ];
                    }
                    $total_spent_time_day_and_users_separately['datasets'][$projectId][$userId]['data'][$timeInterval->date_at] = $time;
                });

            foreach ($total_spent_time_day_and_users_separately['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_day_and_users_separately['datasets'][$key], 'data');
            }
            $result['total_spent_time_day_and_users_separately'] = $total_spent_time_day_and_users_separately;
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
