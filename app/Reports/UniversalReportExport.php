<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Enums\UniversalReport as EnumsUniversalReport;
use App\Helpers\ReportHelper;
use App\Models\CronTaskWorkers;
use App\Models\Project;
use App\Models\UniversalReport;
use ArrayObject;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Carbon\CarbonPeriod;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Settings;

class UniversalReportExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles, WithDefaultStyles, WithCharts
{
    use Exportable;

    private array $periodDates;
    private UniversalReport $report;
    private string $colsDateFormat;
    private readonly CarbonPeriod $period;

    public function __construct(
        private readonly int                  $id,
        private readonly Carbon               $startAt,
        private readonly Carbon               $endAt,
        private readonly string               $companyTimezone,
        private readonly string               $userTimezone,
    )
    {
        $this->report = UniversalReport::find($id);
        $this->period = CarbonPeriod::create(
            $this->startAt->clone()->setTimezone($this->userTimezone),
            $this->endAt->clone()->setTimezone($this->userTimezone)
        );
        $this->periodDates = $this->getPeriodDates($this->period);
    }

    public function collection(): Collection
    {
        $that = $this;

        switch ($this->report->main) {
            case EnumsUniversalReport::PROJECT:
                $result = $this->collectionProject();
                return $result;
                break;
            case EnumsUniversalReport::USER:
                $result = $this->collectionUser();
                return $result;
                break;
            case EnumsUniversalReport::TASK:
                $result = $this->collectionTask();
                return $result;
                break;
            // default:
            //     return throw new Exception('Неправильно передана основа');

        }
    }

    public function charts()
    {
        $label      = [new DataSeriesValues('String', 'Worksheet!$B$1', null, 1)];
        $categories = [new DataSeriesValues('String', 'Worksheet!$B$2:$B$5', null, 4)];
        $values     = [new DataSeriesValues('Number', 'Worksheet!$A$2:$A$5', null, 4)];

        $series = new DataSeries(DataSeries::TYPE_PIECHART, DataSeries::GROUPING_STANDARD,
            range(0, \count($values) - 1), $label, $categories, $values);
        $plot   = new PlotArea(null, [$series]);

        $legend = new Legend();
        $chart  = new Chart('chart name', new Title('chart title'), $legend, $plot);

        return $chart;
    }

    /**
     * @param $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        $that = $this;
        return $row->groupBy('user_id')->map(
            static function ($collection) use ($that) {
                $interval = CarbonInterval::seconds($collection->sum('durationAtSelectedPeriod'));

                return array_merge(
                    array_values($collection->first()->only(['full_name'])),
                    [
                        $interval->cascade()->forHumans(['short' => true]),
                        round($interval->totalHours, 3),
                        ...$that->intervalsByDay($collection)
                    ]
                );
            }
        )->all();
    }

    private function intervalsByDay(Collection $intervals): array
    {
        $intervalsByDay = [];

        foreach ($this->periodDates as $date) {
            $workedAtDate = $intervals->sum(fn($item) => (
                $item->durationByDay[$date] ?? 0
            ));
            $intervalsByDay[] = round(CarbonInterval::seconds($workedAtDate)->totalHours, 3);
        }


        return $intervalsByDay;
    }

    public function collectionUser(): Collection
    {
        $onlyOne = [
            'u_full_name',
            'u_email',
            'total_spent_time',
        ];
        $skipValues = [
            't_id',
            'u_id',
            'p_id',
        ];
        $data = $this->queryReportUser();
        // dd($data, 'ds');
        $result = [];
        foreach ($data as $user) {
            $p_id = $user?->p_id ?? null;
            $u_id = $user->u_id;
            $t_id = $user?->t_id ?? null;
            if (!array_key_exists($u_id, $result)) {
                $result[$u_id] = [];

                foreach ($user as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }
                    if (array_key_exists($key, $result[$u_id]) && in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    // dd($key);
                    if (in_array($key, ['p_name', 'p_created_at', 'p_description', 'p_important'], true) && !is_null($p_id)) {
                        $result[$u_id]['projects'][$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (in_array($key, ['t_task_name', 't_priority', 't_status', 't_due_date', 't_estimate', 't_description'], true) && !is_null($t_id)) {
                        $result[$u_id]['projects'][$p_id]['tasks'][$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        $result[$u_id]['worked_time_day'][$user->date_at] = $user->total_spent_time_by_day;
                        continue;
                    }

                    if (!array_key_exists($key, $result[$u_id]) && in_array($key, $onlyOne, true)) {
                        $result[$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                        continue;
                    }
                }
            } else {
                foreach ($user as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }

                    if (in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    if (in_array($key, ['p_name', 'p_created_at', 'p_description', 'p_important'], true) && !is_null($p_id)) {
                        $result[$u_id]['projects'][$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (in_array($key, ['t_task_name', 't_priority', 't_status', 't_due_date', 't_estimate', 't_description'], true) && !is_null($t_id)) {
                        $result[$u_id]['projects'][$p_id]['tasks'][$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        $result[$u_id]['worked_time_day'][$user->date_at] = $user->total_spent_time_by_day;
                        continue;
                    }
                }
            }
        }
        // dd($result, $this->period, $this->periodDates);
        foreach ($this->periodDates as $date) {
            // dd($result[1]['worked_time_day']['']);
            foreach ($result as $key => $report) {
                unset($result[$key]['worked_time_day']['']);
                if (!array_key_exists($date, $report['worked_time_day'])) {
                    $result[$key]['worked_time_day'][$date] = 0;
                }
            }
        }
        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name
        ]);
    }

    public function collectionTask(): Collection
    {

        // dd(in_array('total_spent_time_by_day', $this->report->fields['calculations']));
        $onlyOne = [
            't_priority',
            't_status',
            't_task_name',
            't_description',
            'p_description',
            't_due_date',
            't_estimate',
            'p_name',
            'p_created_at',
            'p_important',
            'total_spent_time',
        ];
        $skipValues = [
            't_id',
            'u_id',
            'p_id',
            'date_at',
        ];
        $data = $this->queryReportTask();
        // dd($data, 'ds');
        $result = [];
        foreach ($data as $task) {
            $p_id = $task->p_id;
            $u_id = $task->u_id;
            $t_id = $task->t_id;

            if (!array_key_exists($t_id, $result)) {
                $result[$t_id] = [];

                foreach ($task as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }

                    if (array_key_exists($key, $result[$t_id]) && in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    if(in_array($key, ['t_task_name', 't_description', 't_due_date', 't_estimate', 't_priority', 't_status'])) {
                        $result[$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (in_array($key, ['p_created_at', 'p_description', 'p_important', 'p_name', ], true)) {
                        $result[$t_id]['project'][preg_replace('/p_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true) && !array_key_exists('users', $result[$t_id])
                    || in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true) && !in_array($key, array_keys($result[$t_id]['users'][$u_id]))) {
                        $result[$t_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        // dump($t_id, $task->date_at, $task->total_spent_time_by_day);
                        $result[$t_id]['worked_time_day'][$task->date_at] = $task->total_spent_time_by_day;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_user_and_day') {
                        $result[$t_id]['users'][$u_id]['workers_day'][$task->date_at] = $task->total_spent_time_by_user_and_day;
                        continue;
                    }

                    if ($key === 'total_spent_time') {
                        $result[$t_id][$key] = $task->total_spent_time;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_user') {
                        $result[$t_id]['users'][$u_id][$key] = $task->total_spent_time_by_user;
                        continue;
                    }

                    if (!array_key_exists($key, $result[$t_id]) && in_array($key, $onlyOne, true)) {
                        $result[$t_id][$key] = $value;
                        continue;
                    }

                    if (array_key_exists($key, $result[$p_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$t_id][$key], $value);
                        continue;
                    } else if (!array_key_exists($key, $result[$t_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$t_id], [$key => $value]);
                        continue;
                    }
                }
            } else {
                foreach ($task as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }

                    if (in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    if ($key === 'u_full_name' || $key === 'u_email' || $key === 'total_spent_time_by_user') {
                        $result[$t_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        // dump($t_id, $task->date_at, $task->total_spent_time_by_day);
                        $result[$t_id]['worked_time_day'][$task->date_at] = $task->total_spent_time_by_day;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_user_and_day') {
                        $result[$t_id]['users'][$u_id]['workers_day'][$task->date_at] = $task->total_spent_time_by_user_and_day;
                        continue;
                    }
                }
            }
        }
        if (
            in_array('total_spent_time_by_day', $this->report->fields['calculations'])
        ) {
            foreach ($this->periodDates as $date) {
            // dd($result[1]['worked_time_day']['']);
                foreach ($result as $key => $report) {
                    unset($result[$key]['worked_time_day']['']);
                    if (!array_key_exists($date, $report['worked_time_day'])) {
                        $result[$key]['worked_time_day'][$date] = 0;
                    }
                }
            }
        }

        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name
        ]);
    }

    public function collectionProject(): Collection
    {
        $onlyOne = [
            'name',
            'created_at',
            'description',
            'important',
            'priority',
        ];
        $skipValues = [
            'default_priority_id',
            'priority_id',
            'st_id',
            'project_id',
            'id',
            't_id',
            'user_id',
            'task_id',
            'u_id',
            'p_id',
            'date_at',
        ];
        $data = $this->queryReportProject();
        $result = [];
        foreach ($data as $project) {
            $p_id = $project->p_id;
            $u_id = $project->u_id;

            if (!array_key_exists($p_id, $result)) {
                $result[$p_id] = [];


                foreach ($project as $key => $value) {
                    if($key === 'status') {
                        // dd($key, $value, $project);
                        // dd($value);
                    }
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }


                    if (array_key_exists($key, $result[$p_id]) && in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    if(in_array($key, ['t_task_name', 't_priority', 'status', 't_description', 't_due_date', 't_estimate'])) {
                        $result[$p_id]['tasks'][$project->t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true)) {
                        $result[$p_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        $result[$p_id]['worked_time_day'][$project->date_at] = $project->total_spent_time_by_day;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_user_and_day') {
                        $result[$p_id]['users'][$u_id]['workers_day'][$project->date_at] = $project->total_spent_time_by_user_and_day;
                        continue;
                    }

                    if ($key === 'status') {
                        $value === 'Open' ? $result[$p_id]['statuses']['open'] = true : $result[$p_id]['statuses']['closed'] = true;
                        continue;
                    }

                    if (!array_key_exists(preg_replace('/p_/', '', $key, 1), $result[$p_id]) && in_array(preg_replace('/p_/', '', $key, 1), $onlyOne, true)) {
                        $result[$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if (array_key_exists(preg_replace('/p_/', '', $key, 1), $result[$p_id]) && !in_array(preg_replace('/p_/', '', $key, 1), $onlyOne, true)) {
                        array_push($result[$p_id][preg_replace('/p_/', '', $key, 1)], $value);
                        continue;
                    } else if (!array_key_exists($key, $result[$p_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$p_id], [$key => $value]);
                        continue;
                    }
                    // status, task_name, full_name, email, total_spent_time_by_user, total_spent_time_by_user_and_day, date_at, total_spent_time_by_day *Not Only One
                    // name, created_at, description, important, priority, status, due_date, estimate,
                }
            } else {
                foreach ($project as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                        continue;
                    }

                    if (in_array($key, $onlyOne, true)) {
                        continue;
                    }

                    if(in_array($key, ['t_task_name', 'priority', 'status', 't_description', 't_due_date', 't_estimate'])) {
                        $result[$p_id]['tasks'][$project->t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'status') {
                        $value === 'Open' ? $result[$p_id]['statuses']['open'] = true : $result[$p_id]['statuses']['closed'] = true;
                        continue;
                    }
                    if (in_array(preg_replace('/u_/', '', $key, 1), ['full_name', 'email', 'total_spent_time_by_user'])) {
                        $result[$p_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                        continue;
                    }

                    if ($key === 'total_spent_time_by_day') {
                        $result[$p_id]['worked_time_day'][$project->date_at] = $project->total_spent_time_by_day;
                        continue;
                    }
                    if ($key === 'total_spent_time_by_user_and_day') {
                        $result[$p_id]['users'][$u_id]['workers_day'][$project->date_at] = $project->total_spent_time_by_user_and_day;
                        continue;
                    }
                }
            }
        }

        foreach ($this->periodDates as $date) {
            foreach ($result as $key => $report) {
                unset($result[$key]['worked_time_day']['']);
                if (!array_key_exists($date, $report['worked_time_day'])) {
                    $result[$key]['worked_time_day'][$date] = 0;
                }

                foreach ($report['users'] as $k => $user) {
                    unset($result[$key]['users'][$k]['workers_day']['']);

                    if (!array_key_exists($date, $user['workers_day'])) {
                        $result[$key]['users'][$k]['workers_day'][$date] = 0;
                    }
                }
            }
        }

        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name
        ]);
    }

    private function queryReportUser()
    {
        $sqlWith = '';
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = '';

        $users = $this->generateSqlRaw('main', $this->report->fields['main'], 'u', 'users', 'u', '', false, true, false, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlGroupBy .= 'u.id';
        $sqlGroupBy .= ', p.id';
        $projectsUsers = $this->generateSqlRaw('projects', [], 'pu', 'projects_users', 'projects_users', 'u.id=pu.user_id', false, false, true, false);
        $sqlJoin .= $projectsUsers['sqlJoin'];

        $projects = $this->generateSqlRaw('projects', $this->report->fields['projects'], 'p', 'projects', 'projects', 'pu.project_id=p.id', false, true, true, false);
        $sqlSelect .= $projects['sqlSelect'];
        $sqlJoin .= $projects['sqlJoin'];

        if (count($this->report->fields['tasks']) > 0) {
            $sqlGroupBy .= ', t.id';
            $tasksUsers = $this->generateSqlRaw('tasks', [], 'tu', 'tasks_users', 'tasks_users', 'u.id=tu.user_id', false, false, true, false);
            $sqlJoin .= $tasksUsers['sqlJoin'];
            $cloneTasksFields = $this->report->fields['tasks'];
            $afterTasksTableJoins = '';
            if (in_array('priority', $this->report->fields['tasks'], true)) {
                $afterTasksTableJoins .= "LEFT JOIN priorities AS pr ON t.priority_id=pr.id
                ";
                $sqlSelect .= "pr.name as t_priority, ";
                unset($cloneTasksFields[array_search('priority', $cloneTasksFields)]);
            }
            if (in_array('status', $this->report->fields['tasks'], true)) {
                $afterTasksTableJoins .= "LEFT JOIN statuses AS s ON t.status_id=s.id
                ";
                $sqlSelect .= "s.name as t_status, ";
                unset($cloneTasksFields[array_search('status', $cloneTasksFields)]);
            }

            $tasks = $this->generateSqlRaw('tasks', $cloneTasksFields, 't', 'tasks', 'tasks', 'tu.task_id=t.id AND p.id=t.project_id', false, true, true, false);
            $sqlSelect .= $tasks['sqlSelect'];
            $sqlJoin .= $tasks['sqlJoin'];
            $sqlJoin .= $afterTasksTableJoins;
        }

        if (isset($this->report->fields['calculations']) && count($this->report->fields['calculations']) > 0) {
            $sqlWith .= "WITH ";
            if (in_array('total_spent_time', $this->report->fields['calculations'], true)) {
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

            if (in_array('total_spent_time_by_day', $this->report->fields['calculations'], true)) {
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

            $sqlWith = rtrim($sqlWith, '), ').') ';
        }
        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "u.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }
        $sqlSelect = rtrim($sqlSelect, ', ').' ';

        // dd(
        return DB::select(
            "$sqlWith SELECT {$sqlSelect}
            FROM users AS u
            $sqlJoin
            WHERE $sqlWhere
            GROUP BY $sqlGroupBy"
        );
        // );
    }


    // Таск завершён. Осталось только нормально обработать поле date_at в collectionTask UPD: время по юзерам и дням не приходит
    private function queryReportTask()
    {
        $sqlWith = '';
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = 't.id';

        $cloneTasksFields = $this->report->fields['main'];
        if (in_array('priority', $this->report->fields['main'], true)) {
            $sqlJoin .= "LEFT JOIN priorities AS pr ON t.priority_id=pr.id
            ";
            $sqlSelect .= "pr.name as t_priority, ";
            unset($cloneTasksFields[array_search('priority', $cloneTasksFields)]);
        }
        if (in_array('status', $this->report->fields['main'], true)) {
            $sqlJoin .= "LEFT JOIN statuses AS s ON t.status_id=s.id
            ";
            $sqlSelect .= "s.name as t_status, ";
            unset($cloneTasksFields[array_search('status', $cloneTasksFields)]);
        }
        $tasks = $this->generateSqlRaw('main', $cloneTasksFields, 't', 'tasks', 'tasks', '', false, true, false, false);
        $sqlSelect .= $tasks['sqlSelect'];

        $tasksUsers = $this->generateSqlRaw('users', [], 'tu', 'tasks_users', 'tasks_users', 't.id=tu.task_id', false, true, true, false);
        $sqlJoin .= $tasksUsers['sqlJoin'];

        $users = $this->generateSqlRaw('users', $this->report->fields['users'], 'u', 'users', 'users', 'tu.user_id=u.id', false, true, true, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlJoin .= $users['sqlJoin'];
        $sqlGroupBy .= ', u.id';

        $projects = $this->generateSqlRaw('projects', $this->report->fields['projects'], 'p', 'projects', 'projects', 't.project_id=p.id', false, true, true, false);
        $sqlSelect .= $projects['sqlSelect'];
        $sqlJoin .= $projects['sqlJoin'];
        $sqlGroupBy .= ', p.id';

        if (isset($this->report->fields['calculations']) && count($this->report->fields['calculations']) > 0) {
            $sqlWith .= "WITH ";

            if (in_array('total_spent_time_by_user', $this->report->fields['calculations'], true)) {
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

            if (in_array('total_spent_time', $this->report->fields['calculations'], true)) {
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

            if (in_array('total_spent_time_by_user_and_day', $this->report->fields['calculations'], true)) {
                $sqlWith .= "total_spent_time_by_user_and_day AS (
                    SELECT user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user_and_day
                    FROM time_intervals
                    WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                    AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                    AND deleted_at IS NULL
                    GROUP BY user_id, date_at, task_id
                ) ";

                $sqlSelect .= "ts_time_user_day.total_spent_time_by_user_and_day, ts_time_user_day.date_at, ";
                $sqlJoin .= "LEFT JOIN total_spent_time_by_user_and_day AS ts_time_user_day ON u.id=ts_time_user_day.user_id ";
                $sqlGroupBy .= ', ts_time_user_day.date_at';
            }

            if (in_array('total_spent_time_by_day', $this->report->fields['calculations'], true)) {
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

            $sqlWith = rtrim($sqlWith, '), ').') ';
        }

        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "t.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }

        $sqlSelect = rtrim($sqlSelect, ', ').' ';
        // dd(
        return DB::select(
            "$sqlWith SELECT {$sqlSelect}
            FROM tasks AS t
            $sqlJoin
            WHERE $sqlWhere
            GROUP BY $sqlGroupBy"
         );
        // );
    }

    protected function valueExistsInArray($arr, $findingValue): bool
    {
        return array_search($findingValue, $arr) === false ? false : true;
    }

    private function generateSqlRaw(string $key, array $arr, string $prefix, string $table, string $alias, string $connector, bool $select = false, bool $sqlSelect = false, bool $sqlJoin = false, bool $sqlWith = false)
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
            // $result['sqlWith'] .= "$alias AS (
            //     SELECT id
            //     FROM $table
            // ),
            // ";
            if ($sqlSelect) {
                $result['sqlSelect'] .= "$prefix.id as $id, ";
            }

            $result['sqlJoin'] .= "LEFT JOIN $alias AS $prefix ON $connector
            ";
        }

        return $result;
    }
    // Проект надо переписать группировку. Убрать зависимости join от других необязательных джойнов. Проверить правильно ли высчитывается всё время
    private function queryReportProject()
    {
        $copyTaskField = (new ArrayObject($this->report->fields['tasks']))->getArrayCopy();

        if ($this->valueExistsInArray($copyTaskField, 'priority')) {
            unset($copyTaskField[array_search('priority', $copyTaskField)]);
        }

        if ($this->valueExistsInArray($copyTaskField, 'status')) {
            unset($copyTaskField[array_search('status', $copyTaskField)]);
        }
        $sqlWith = "";
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = '';
        $projects = $this->generateSqlRaw('main', $this->report->fields['main'], 'p', 'projects', 'p', '', false, true, false, false);
        $sqlSelect .= $projects['sqlSelect'];

        $cloneTasksFields = $this->report->fields['tasks'];
        if (in_array('priority', $this->report->fields['tasks'], true)) {
            $sqlJoin .= "LEFT JOIN priorities AS pr ON p.default_priority_id=pr.id
            ";
            $sqlSelect .= "pr.name as priority, ";
            unset($cloneTasksFields[array_search('priority', $cloneTasksFields)]);
        }
        if (in_array('status', $this->report->fields['tasks'], true)) {
            $sqlJoin .= "LEFT JOIN projects_statuses AS ps ON p.id=ps.project_id LEFT JOIN statuses AS s ON ps.status_id=s.id
            ";
            $sqlSelect .= "s.name as status, ";
            unset($cloneTasksFields[array_search('status', $cloneTasksFields)]);
        }

        $tasks = $this->generateSqlRaw('tasks', $cloneTasksFields, 't', 'tasks', 'tasks', 'p.id=t.project_id', false, true, true, false);
        $sqlSelect .= $tasks['sqlSelect'];
        $sqlJoin .= $tasks['sqlJoin'];

        $tasksUsers = $this->generateSqlRaw('tasks', [], 'tu', 'tasks_users', 'tasks_users', 't.id=tu.task_id', false, true, true, false);
        // $sqlSelect .= preg_replace('/tu.id as tu_id, /', 'tu.user_id, tu.task_id, ', $tasksUsers['sqlSelect']);
        // $sqlSelect .= 'tu.user'
        $sqlJoin .= $tasksUsers['sqlJoin'];

        $users = $this->generateSqlRaw('users', $this->report->fields['users'], 'u', 'users', 'users', 'tu.user_id=u.id', false, true, true, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlJoin .= $users['sqlJoin'];
        $sqlWith .= $users['sqlWith'];

        if (isset($this->report->fields['calculations']) && count($this->report->fields['calculations']) > 0) {
            $sqlWith .= "WITH ";
            if ($this->valueExistsInArray($this->report->fields['calculations'], 'total_spent_time_by_user')) {
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

            if ($this->valueExistsInArray($this->report->fields['calculations'], 'total_spent_time_by_day_and_user')) {
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

            if ($this->valueExistsInArray($this->report->fields['calculations'], 'total_spent_time_by_day')) {
                $sqlWith .= "total_spent_time_by_day AS (
                    SELECT user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day
                    FROM time_intervals
                    WHERE start_at>='{$this->startAt->format('Y-m-d')} 00:00:00'
                    AND end_at<='{$this->endAt->format('Y-m-d')} 23:59:59'
                    GROUP BY date_at
                ) ";

                $sqlSelect .= "ts_time_day.total_spent_time_by_day, ";
                $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON u.id=ts_time_day.user_id ";
                // $sqlJoin .= "LEFT JOIN total_spent_time_by_day AS ts_time_day ON ts_time_user_day.date_at=ts_time_day.date_at ";
            }


        }

        $sqlGroupBy .= 'p.id, u.id, t.id';

        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "p.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }
        $sqlSelect = rtrim($sqlSelect, ', ').' ';


        return DB::select(
            "$sqlWith SELECT {$sqlSelect}
            FROM projects AS p
            $sqlJoin
            WHERE $sqlWhere
            GROUP BY $sqlGroupBy"
        );

        // dd(DB::select("WITH
        //     total_spent_time_by_user AS (
        //         SELECT id, user_id, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user
        //         FROM time_intervals
        //         WHERE start_at>='{$this->startAt->format('y-m-d')} 00:00:00'
        //         AND end_at<='{$this->endAt->format('y-m-d')} 23:59:59'
        //         GROUP BY user_id
        //     ),
        //     total_spent_time_by_day AS (
        //         SELECT id, user_id, task_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_day
        //         FROM time_intervals
        //         WHERE start_at>='{$this->startAt->format('y-m-d')} 00:00:00'
        //         AND end_at<='{$this->endAt->format('y-m-d')} 23:59:59'
        //         GROUP BY date_at
        //     ),
        //     total_spent_time_by_user_and_day AS (
        //         SELECT id, user_id, DATE(start_at) as date_at, SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as total_spent_time_by_user_and_day
        //         FROM time_intervals
        //         WHERE start_at>='{$this->startAt->format('y-m-d')} 00:00:00'
        //         AND end_at<='{$this->endAt->format('y-m-d')} 23:59:59'
        //         GROUP BY user_id, date_at
        //     ),
        //     userInfo AS (
        //         SELECT id, full_name
        //         FROM users
        //     )
        //     SELECT p.id, p.name, t.id, t.task_name, u.id, t.project_id, ts_time_user.user_id, tu.user_id, tu.task_id, ts_time_user.total_spent_time_by_user, ts_time_user_day.total_spent_time_by_user_and_day, ts_time_user_day.date_at, ts_time_day.total_spent_time_by_day
        //     FROM projects AS p
        //     JOIN tasks AS t ON p.id=t.project_id
        //     JOIN tasks_users AS tu ON t.id=tu.task_id
        //     JOIN userInfo AS u ON tu.user_id=u.id
        //     JOIN total_spent_time_by_user AS ts_time_user ON u.id=ts_time_user.user_id
        //     JOIN total_spent_time_by_user_and_day AS ts_time_user_day ON u.id=ts_time_user_day.user_id
        //     JOIN total_spent_time_by_day AS ts_time_day ON ts_time_user_day.date_at=ts_time_day.date_at
        //     WHERE p.id=1
        //     GROUP BY p.id, u.id, t.id, ts_time_user_day.date_at"));
    }
    public function headings(): array
    {
        return [
            'User Name',
            'Hours',
            'Hours (decimal)',
            ...collect($this->periodDates)->map(fn($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    private function getPeriodDates($period): array
    {
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format(ReportHelper::$dateFormat);
        }
        return $dates;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]
        ];
    }

    public function getReportId(): string
    {
        return 'dashboard_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Dashboard_Report');
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
    }
}
