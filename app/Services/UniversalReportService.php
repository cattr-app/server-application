<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\UniversalReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;

class UniversalReportService
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

    /**
     * This function generate and return WITH, JOIN, GROUP BY a string for project sql
     * @param array[] $calculations This variable get array selected calculations for select project report
     * @return array returned array with JOIN, GROUP BY, WITH a strings
    */
    public function sqlWithForProject(array $calculations = [])
    {
        if (count($calculations) === 0) {
            return ['sqlWith' => '', 'sqlJoin' => '', 'sqlGroupBy' => '', 'sqlSelect' => ''];
        }

        $sqlWith = "WITH ";
        $sqlSelect = "";
        $sqlJoin = "";
        $sqlGroupBy = "";

        if (in_array('total_spent_time_by_user', $calculations)) {
            $sqlWith .= "total_spent_time_by_user AS (
                SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                GROUP BY user_id
            ), ";

            $sqlSelect .= "ts_time_user.user_id, ts_time_user.total_spent_time_by_user, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_user AS ts_time_user ON u.id=ts_time_user.user_id ";
        }

        if (in_array('total_spent_time_by_day_and_user', $calculations)) {
            $sqlWith .= "total_spent_time_by_user_and_day AS (
                SELECT user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user_and_day
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                GROUP BY user_id, date_at
            ), ";

            $sqlGroupBy .= 'ts_time_user_day.date_at, ';
            $sqlSelect .= "ts_time_user_day.total_spent_time_by_user_and_day, ts_time_user_day.date_at, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_user_and_day AS ts_time_user_day ON u.id=ts_time_user_day.user_id ";
        }

        if (in_array('total_spent_time_by_day', $calculations)) {
            $sqlWith .= "total_spent_time_by_day AS (
                SELECT user_id, task_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                GROUP BY date_at, (SELECT project_id from tasks WHERE tasks.id=task_id)
            ) ";

            $sqlSelect .= "ts_time_day.total_spent_time_by_day, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON u.id=ts_time_day.user_id ";
            // $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON ts_time_day.task_id=t.id ";
            // $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON ts_time_user_day.date_at=ts_time_day.date_at ";
        }

        return [
            'sqlWith' => $sqlWith,
            'sqlJoin' => $sqlJoin,
            'sqlGroupBy' => $sqlGroupBy,
            'sqlSelect' => $sqlSelect,
        ];
    }
    public function sqlWithForUser(array $calculations = [])
    {
        if (count($calculations) === 0) {
            return ['sqlWith' => '', 'sqlJoin' => '', 'sqlGroupBy' => '', 'sqlSelect' => ''];
        }

        $sqlWith = "WITH ";
        $sqlSelect = "";
        $sqlJoin = "";
        $sqlGroupBy = "";

        if (in_array('total_spent_time', $calculations, true)) {
            $sqlWith .= "total_spent_time AS (
                SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                GROUP BY user_id
            ), ";

            $sqlSelect .= "ts_time_user.total_spent_time, ";
            $sqlJoin .= "LEFT JOIN total_spent_time AS ts_time_user ON u.id=ts_time_user.user_id ";
        }

        if (in_array('total_spent_time_by_day', $calculations, true)) {
            $sqlWith .= "total_spent_time_by_day AS (
                SELECT user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                GROUP BY user_id, date_at
            ) ";

            $sqlSelect .= "ts_time_day.total_spent_time_by_day, ts_time_day.date_at, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON u.id=ts_time_day.user_id ";
            $sqlGroupBy .= ', ts_time_day.date_at';
        }

        return [
            'sqlWith' => $sqlWith,
            'sqlJoin' => $sqlJoin,
            'sqlGroupBy' => $sqlGroupBy,
            'sqlSelect' => $sqlSelect,
        ];

    }
    public function sqlWithForTask(array $calculations = [])
    {
        if (count($calculations) === 0) {
            return ['sqlWith' => '', 'sqlJoin' => '', 'sqlGroupBy' => '', 'sqlSelect' => ''];
        }

        $sqlWith = "WITH ";
        $sqlSelect = "";
        $sqlJoin = "";
        $sqlGroupBy = "";

        if (in_array('total_spent_time_by_user', $calculations, true)) {
            $sqlWith .= "total_spent_time_by_user AS (
                SELECT user_id, task_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                AND deleted_at IS NULL
                GROUP BY user_id, task_id
            ), ";

            $sqlSelect .= "ts_time_user.total_spent_time_by_user, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_user AS ts_time_user ON t.id=ts_time_user.task_id AND u.id=ts_time_user.user_id ";
        }

        if (in_array('total_spent_time', $calculations, true)) {
            $sqlWith .= "total_spent_time AS (
                SELECT task_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                AND deleted_at IS NULL
                GROUP BY task_id
            ), ";

            $sqlSelect .= "ts_time.total_spent_time, ";
            $sqlJoin .= "LEFT JOIN total_spent_time AS ts_time ON t.id=ts_time.task_id ";
        }

        if (in_array('total_spent_time_by_day_and_user', $calculations, true)) {
            $sqlWith .= "total_spent_time_by_user_and_day AS (
                SELECT user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user_and_day
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                AND deleted_at IS NULL
                GROUP BY user_id, date_at, task_id
            ), ";

            $sqlSelect .= "ts_time_user_day.total_spent_time_by_user_and_day, ts_time_user_day.date_at, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_user_and_day AS ts_time_user_day ON u.id=ts_time_user_day.user_id ";
            $sqlGroupBy .= ', ts_time_user_day.date_at';
        }

        if (in_array('total_spent_time_by_day', $calculations, true)) {
            $sqlWith .= "total_spent_time_by_day AS (
                SELECT task_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day
                FROM time_intervals
                WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                AND deleted_at IS NULL
                GROUP BY task_id, date_at
            ) ";

            $sqlSelect .= "ts_time_day.total_spent_time_by_day, ts_time_day.date_at, ";
            $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON t.id=ts_time_day.task_id ";
            $sqlGroupBy .= ', ts_time_day.date_at';
        }

        return [
            'sqlWith' => $sqlWith,
            'sqlJoin' => $sqlJoin,
            'sqlGroupBy' => $sqlGroupBy,
            'sqlSelect' => $sqlSelect,
        ];
    }

    public function generateSqlRaw(string $key, array $arr, string $prefix, string $table, string $alias, string $connector, bool $select = false, bool $sqlSelect = false, bool $sqlJoin = false, bool $sqlWith = false)
    {
        $fields = $this->report->main->fields()[$key];
        $result = [
            'select' => '',
            'sqlSelect' => '',
            'sqlJoin' => '',
            'sqlWith' => '',
        ];

        $id = $prefix.'_id';

        if (count($arr) > 0) {
            $result['sqlSelect'] .= "$prefix.id as $id, ";

            foreach ($arr as $key => $value) {
                if(in_array($value, $fields, true)) {

                    if ($select) {
                        $result['select'] .= "$value";
                        if (++$key === count($arr)) {
                            $result['select'] .= ' ';
                        } else {
                            $result['select'] .= ', ';
                        }
                    }

                    if ($sqlSelect) {
                        $result['sqlSelect'] .= "$prefix.$value as {$prefix}_$value, ";
                    }

                }
            }

            if ($sqlJoin) {
                $result['sqlJoin'] .= "LEFT JOIN $alias AS $prefix ON $connector
                ";
            }

            if ($sqlWith) {
                if(strlen($result['select']) > 0) {
                    $result['select'] = "id, {$result['select']}";
                } else {
                    $result['select'] = "id ";
                }

                $result['sqlWith'] .= "$alias AS (
                    SELECT {$result['select']}
                    FROM $table
                ),
                ";
            }
        } else {
            if ($sqlSelect) {
                $result['sqlSelect'] .= "$prefix.id as $id, ";
            }

            $result['sqlJoin'] .= "LEFT JOIN $alias AS $prefix ON $connector
            ";
        }

        return $result;
    }

    public function createChart(string $chart, array $charts, array $values = []): array
    {
        if (in_array($chart, $charts)) {
            return [
                'active' => true,
                'label' => $chart,
                'values' => $values,
            ];
        }

        return [
            'active' => false,
            'label' => '',
            'values' => [],
        ];
    }

    public function usersCharts() {
        $result = [];

        if (count($this->report->charts) === 0) {
            return $result;
        }
        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_by_day = [
                'datasets' => [],
            ];
            // TimeInterval::selectRaw('user_id, full_name, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day, DATE(start_at) as date_at')
            //     ->whereIn('user_id', $this->report->data_objects)
            //     ->where([
            //         ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
            //         ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
            //     ])
            //     ->leftJoin('users', function($join) {
            //         $join
            //             ->on('time_intervals.user_id', '=', 'users.id')
            //             ->select('full_name');
            //     })
            //     ->groupBy('user_id', 'date_at')
            //     ->get()
            //     ->each(function($i) use (&$total_spent_time_by_day) {
            //         $time = sprintf("%02d.%02d", floor($i->total_spent_time_by_day / 3600), floor($i->total_spent_time_by_day / 60) % 60);

            //         if(!array_key_exists($i->user_id, $total_spent_time_by_day['datasets'])) {
            //             $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
            //             return $total_spent_time_by_day['datasets'][$i->user_id] = [
            //                 'label' => $i->full_name,
            //                 'borderColor' => $color,
            //                 'backgroundColor' => $color,
            //                 'data' => [$i->date_at => $time],
            //             ];
            //         }

            //         return $total_spent_time_by_day['datasets'][$i->user_id]['data'][$i->date_at] = $time;
            //     });
            User::selectRaw('users.id, users.full_name, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_by_day, DATE(time_intervals.start_at) as date_at')
                ->whereIn('users.id', $this->report->data_objects)
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('users.id', '=', 'time_intervals.user_id')
                        ->select('start_at', 'end_at')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->groupBy('users.id', 'date_at')
                ->get()
                ->each(function($i) use (&$total_spent_time_by_day) {
                    $time = sprintf("%02d.%02d", floor($i->total_spent_time_by_day / 3600), floor($i->total_spent_time_by_day / 60) % 60);

                    if(!array_key_exists($i->id, $total_spent_time_by_day['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        return $total_spent_time_by_day['datasets'][$i->id] = [
                            'label' => $i->full_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    }

                    return $total_spent_time_by_day['datasets'][$i->id]['data'][$i->date_at] = $time;
                });

            $this->fillNullDatesAsZeroTime($total_spent_time_by_day['datasets'], 'data');

            $result['total_spent_time_day'] = $total_spent_time_by_day;
        }

        if (in_array('total_spent_time_day_and_tasks', $this->report->charts)) {
            $total_spent_time_by_day_and_tasks = [
                'datasets' => [],
            ];

            // TimeInterval::selectRaw('user_id, task_name, task_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day_and_tasks, DATE(start_at) as date_at')
            //     ->whereIn('user_id', $this->report->data_objects)
            //     ->where([
            //         ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
            //         ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
            //     ])
            //     ->leftJoin('tasks', function($join) {
            //         $join
            //             ->on('time_intervals.task_id', '=', 'tasks.id')
            //             ->select('task_name');
            //     })
            //     ->groupBy('user_id', 'date_at', 'task_id')
            //     ->get()->each(function($i) use(&$total_spent_time_by_day_and_tasks) {
            //         $time = sprintf("%02d.%02d", floor($i->total_spent_time_by_day_and_tasks / 3600), floor($i->total_spent_time_by_day_and_tasks / 60) % 60);

            //         if(!array_key_exists($i->user_id, $total_spent_time_by_day_and_tasks['datasets'])) {
            //             $total_spent_time_by_day_and_tasks['datasets'][$i->user_id] = [];
            //         }
            //         if(!array_key_exists($i->task_id, $total_spent_time_by_day_and_tasks['datasets'][$i->user_id])) {
            //             $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
            //             return $total_spent_time_by_day_and_tasks['datasets'][$i->user_id][$i->task_id] = [
            //                 'label' => $i->task_name,
            //                 'borderColor' => $color,
            //                 'backgroundColor' => $color,
            //                 'data' => [$i->date_at => $time],
            //             ];
            //         }

            //         return $total_spent_time_by_day_and_tasks['datasets'][$i->user_id][$i->task_id]['data'][$i->date_at] = $time;
            //     });
            User::selectRaw('users.id, tasks.task_name, tasks.id as task_id, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_by_day_and_tasks, DATE(time_intervals.start_at) as date_at')
                ->whereIn('users.id', $this->report->data_objects)
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('users.id', '=', 'time_intervals.user_id')
                        ->select('start_at', 'end_at')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->leftJoin('tasks_users', function($join) {
                    $join
                        ->on('users.id', '=', 'tasks_users.user_id');
                })
                ->leftJoin('tasks', function($join) {
                    $join
                        ->on('tasks_users.task_id', '=', 'tasks.id')
                        ->orOn('time_intervals.task_id', '=', 'tasks.id')
                        ->select('task_name', 'id');
                })
                ->groupBy('users.id', 'date_at', 'tasks.id')
                ->get() // Если пользователь не прикреплён ни к одной задаче и при этом за выбранный период у него нет ни одного интервала, то его данные для диаграммы не на чём строить. Нужно добавить обработку на бэке, что если task_id null, то мы не даём юзеру dataset
                ->each(function($i) use (&$total_spent_time_by_day_and_tasks) {
                    $time = sprintf("%02d.%02d", floor($i->total_spent_time_by_day_and_tasks / 3600), floor($i->total_spent_time_by_day_and_tasks / 60) % 60);

                    if(!array_key_exists($i->user_id, $total_spent_time_by_day_and_tasks['datasets'])) {
                        $total_spent_time_by_day_and_tasks['datasets'][$i->id] = [];
                    }

                    if(!array_key_exists($i->task_id, $total_spent_time_by_day_and_tasks['datasets'][$i->id])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        return $total_spent_time_by_day_and_tasks['datasets'][$i->id][$i->task_id] = [
                            'label' => $i->task_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
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
            User::selectRaw('users.id, projects.name, projects.id as project_id, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_by_day_and_projects, DATE(time_intervals.start_at) as date_at')
                ->whereIn('users.id', $this->report->data_objects)
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('users.id', '=', 'time_intervals.user_id')
                        ->select('time_intervals.start_at', 'time_intervals.end_at')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->leftJoin('projects_users', function($join) {
                    $join
                        ->on('users.id', '=', 'projects_users.user_id');
                })
                ->leftJoin('tasks_users', function($join) {
                    $join
                        ->on('users.id', '=', 'tasks_users.user_id')
                        ->orOn('time_intervals.task_id', '=', 'tasks_users.task_id');
                })
                ->leftJoin('projects', function($join) {
                    $join
                        ->on('projects_users.project_id', '=', 'projects.id')
                        ->on('tasks_users.user_id', '=', 'users.id')
                        ->select('projects.id', 'projects.name');
                })
                ->leftJoin('tasks', function($join) {
                    $join
                        ->on('projects.id', '=', 'tasks.project_id')
                        ->on('tasks_users.task_id', '=', 'tasks.id');
                })
                ->groupBy('users.id', 'date_at', 'projects.id')
                ->get()
                ->each(function($i) use(&$total_spent_time_by_day_and_projects){
                    if(!array_key_exists($i->id, $total_spent_time_by_day_and_projects['datasets'])) {
                        $total_spent_time_by_day_and_projects['datasets'][$i->id] = [];
                    }

                    if(!array_key_exists($i->project_id, $total_spent_time_by_day_and_projects['datasets'][$i->id])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        return $total_spent_time_by_day_and_projects['datasets'][$i->id][$i->project_id] = [
                            'label' => $i->name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => ($i->total_spent_time_by_day_and_projects / 60) / 60],
                        ];
                    }

                    return $total_spent_time_by_day_and_projects['datasets'][$i->id][$i->project_id]['data'][$i->date_at] = ($i->total_spent_time_by_day_and_projects / 60) / 60;
                });

            foreach ($total_spent_time_by_day_and_projects['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_by_day_and_projects['datasets'][$key], 'data');
            }

            $result['total_spent_time_day_and_projects'] = $total_spent_time_by_day_and_projects;
        }

        return $result;
    }

    public function projectsCharts() {
        $result = [];

        if (count($this->report->charts) === 0) {
            return $result;
        }

        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_day = [
                'datasets' => []
            ];
            Project::selectRaw('projects.id, projects.name, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_day, DATE(time_intervals.start_at) as date_at')
                ->whereIn('projects.id', $this->report->data_objects)
                ->leftJoin('tasks', function($join) {
                    $join
                        ->on('projects.id', '=', 'tasks.project_id');
                })
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('tasks.id', '=', 'time_intervals.task_id')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->groupBy('projects.id', 'date_at')
                ->get()
                ->each(function($i) use (&$total_spent_time_day) {
                    $time = sprintf("%02d.%02d", floor($i->total_spent_time_day / 3600), floor($i->total_spent_time_day / 60) % 60);
                    if(!array_key_exists($i->id, $total_spent_time_day['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));

                        return $total_spent_time_day['datasets'][$i->id] = [
                            'label' => $i->name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    }

                    return $total_spent_time_day['datasets'][$i->id]['data'][$i->date_at] = $time;
                });

            $this->fillNullDatesAsZeroTime($total_spent_time_day['datasets'], 'data');

            $result['total_spent_time_day'] = $total_spent_time_day;
        }

        if (in_array('total_spent_time_day_and_users_separately', $this->report->charts)) {
            $total_spent_time_day_and_users_separately = [
                'datasets' => [],
            ];

            Project::selectRaw('projects.id, users.full_name, users.id as user_id, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_day_and_users_separately, DATE(time_intervals.start_at) as date_at')
                ->whereIn('projects.id', $this->report->data_objects)
                ->leftJoin('projects_users', function($join) {
                    $join
                        ->on('projects.id', '=', 'projects_users.project_id');
                })
                ->leftJoin('tasks', function($join) {
                    $join
                        ->on('projects.id', '=', 'tasks.project_id');
                })
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('projects.id', '=', 'tasks.project_id')
                        ->orOn('tasks.id', '=', 'time_intervals.task_id')
                        ->select('user_id')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->leftJoin('users', function($join) {
                    $join
                        ->on('projects_users.user_id', '=', 'users.id')
                        ->orOn('time_intervals.user_id', '=', 'users.id')
                        ->select('full_name');
                })
                ->groupBy('projects.id', 'user_id', 'date_at')
                ->get()
                ->each(function($i) use (&$total_spent_time_day_and_users_separately) {
                    $time = sprintf("%02d.%02d", floor($i->total_spent_time_day_and_users_separately / 3600), floor($i->total_spent_time_day_and_users_separately / 60) % 60);
                    if(!array_key_exists($i->id, $total_spent_time_day_and_users_separately['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));

                        return $total_spent_time_day_and_users_separately['datasets'][$i->id][$i->user_id] = [
                            'label' => $i->full_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    } elseif(!array_key_exists($i->user_id, $total_spent_time_day_and_users_separately['datasets'][$i->id])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));

                        return $total_spent_time_day_and_users_separately['datasets'][$i->id][$i->user_id] = [
                            'label' => $i->full_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    }

                    return $total_spent_time_day_and_users_separately['datasets'][$i->id][$i->user_id]['data'][$i->date_at] = $time;
                });

            foreach ($total_spent_time_day_and_users_separately['datasets'] as $key => $item) {
                $this->fillNullDatesAsZeroTime($total_spent_time_day_and_users_separately['datasets'][$key], 'data');
            }

            // dd($total_spent_time_day_and_users_separately);

            $result['total_spent_time_day_and_users_separately'] = $total_spent_time_day_and_users_separately;
        }

        return $result;
    }

    public function tasksCharts() {
        $result = [];

        if (count($this->report->charts) === 0) {
            return $result;
        }

        if (in_array('total_spent_time_day', $this->report->charts)) {
            $total_spent_time_day = [
                'datasets' => []
            ];
            Task::selectRaw('tasks.id, tasks.task_name, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_day, DATE(time_intervals.start_at) as date_at')
                ->whereIn('tasks.id', $this->report->data_objects)
                // ->leftJoin('projects', function($join) {
                //     $join
                //         ->on('tasks.project_id', '=', 'projects.id');
                // })
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('tasks.id', '=', 'time_intervals.task_id')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 00:00:00"],
                        ]);
                })
                ->groupBy('tasks.id', 'date_at')
                ->get()
                ->each(function($i) use (&$total_spent_time_day) {
                    $time = sprintf("%02d.%02d", floor($i->total_spent_time_day / 3600), floor($i->total_spent_time_day / 60) % 60);
                    if(!array_key_exists($i->id, $total_spent_time_day['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));

                        return $total_spent_time_day['datasets'][$i->id] = [
                            'label' => $i->task_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    }

                    return $total_spent_time_day['datasets'][$i->id]['data'][$i->date_at] = $time;
                });

            $this->fillNullDatesAsZeroTime($total_spent_time_day['datasets'], 'data');

            $result['total_spent_time_day'] = $total_spent_time_day;
        }

        if (in_array('total_spent_time_day_users_separately', $this->report->charts)) {
            $total_spent_time_day_users_separately = [
                'datasets' => [],
            ];

            $r = Task::selectRaw('tasks.id, users.full_name, users.id as user_id, SUM(TIMESTAMPDIFF(SECOND, time_intervals.start_at, time_intervals.end_at)) as total_spent_time_day_users_separately, DATE(time_intervals.start_at) as date_at')
                ->whereIn('tasks.id', $this->report->data_objects)
                ->leftJoin('tasks_users', function($join) {
                    $join
                        ->on('tasks.id', '=', 'tasks_users.task_id');
                })
                ->leftJoin('time_intervals', function($join) {
                    $join
                        ->on('tasks.id', '=', 'time_intervals.task_id')
                        ->where([
                            ['start_at', '>=', "{$this->startAt->format('Y-m-d')} 00:00:00"],
                            ['end_at', '<=', "{$this->endAt->format('Y-m-d')} 23:59:59"],
                        ]);
                })
                ->leftJoin('users', function(JoinClause $join) {
                    $join
                        ->on('time_intervals.user_id', '=', 'users.id')
                        ->orOn('tasks_users.user_id', '=', 'users.id')
                        ->select('full_name')
                        ->distinct();
                })
                ->distinct()
                ->groupBy('tasks.id', 'date_at', 'time_intervals.user_id')
                ->get()
                ->each(function($i) use (&$total_spent_time_day_users_separately) {
                    // $time = sprintf("%02d.%02d", floor($i->total_spent_time_day_users_separately / 3600), floor($i->total_spent_time_day_users_separately / 60) % 60);
                    $time = $i->total_spent_time_day_users_separately;
                    if(!array_key_exists($i->id, $total_spent_time_day_users_separately['datasets'])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
                        // dd($i);
                        return $total_spent_time_day_users_separately['datasets'][$i->id][$i->user_id] = [
                            'label' => $i->full_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    } elseif(!array_key_exists($i->user_id, $total_spent_time_day_users_separately['datasets'][$i->id])) {
                        $color = sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));

                        return $total_spent_time_day_users_separately['datasets'][$i->id][$i->user_id] = [
                            'label' => $i->full_name,
                            'borderColor' => $color,
                            'backgroundColor' => $color,
                            'data' => [$i->date_at => $time],
                        ];
                    }

                    return $total_spent_time_day_users_separately['datasets'][$i->id][$i->user_id]['data'][$i->date_at] = $time;
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

                    if(!array_key_exists($date, $item[$key])) {
                        $datesToFill[$k][$key][$date] = 0.0;
                    }
                    ksort($datesToFill[$k][$key]);
                }
            }
        }
    }
}
