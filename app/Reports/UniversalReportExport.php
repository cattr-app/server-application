<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\UniversalReport as EnumsUniversalReport;
use App\Helpers\ReportHelper;
use App\Models\UniversalReport;
use App\Models\User;
use App\Services\UniversalReportService;
use ArrayObject;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use DB;
use Exception;
use Illuminate\Support\Collection;
use Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


class UniversalReportExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles, WithDefaultStyles, WithMultipleSheets
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
    ) {
        $this->report = UniversalReport::find($id);
        $this->period = CarbonPeriod::create(
            $this->startAt->clone()->setTimezone($this->userTimezone),
            $this->endAt->clone()->setTimezone($this->userTimezone)
        );
        $this->periodDates = $this->getPeriodDates($this->period);
    }

    public function collection(): Collection
    {
        switch ($this->report->main) {
            case EnumsUniversalReport::PROJECT:
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


                return $this->collectionProject($onlyOne, $skipValues);

            case EnumsUniversalReport::USER:
                return $this->collectionUser();

            case EnumsUniversalReport::TASK:
                return $this->collectionTask();
        }
    }

    // public function charts()
    // {
    //     $label      = [new DataSeriesValues('String', 'Worksheet!$B$1', null, 1)];
    //     $categories = [new DataSeriesValues('String', 'Worksheet!$B$2:$B$5', null, 4)];
    //     $values     = [new DataSeriesValues('Number', 'Worksheet!$A$2:$A$5', null, 4)];

    //     $series = new DataSeries(
    //         DataSeries::TYPE_PIECHART,
    //         DataSeries::GROUPING_STANDARD,
    //         range(0, \count($values) - 1),
    //         $label,
    //         $categories,
    //         $values
    //     );
    //     $plot   = new PlotArea(null, [$series]);

    //     $legend = new Legend();
    //     $chart  = new Chart('chart name', new Title('chart title'), $legend, $plot);

    //     return $chart;
    // }

    /**
     * @param $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        if (is_string($row)) {
            return [];
        }


        // Log::error(print_r($row, true));

        //  $that = $this;
        // return collect($row)->groupBy('user_id')->map(
        //     static function ($collection) use ($that) {
        //         $interval = CarbonInterval::seconds($collection->sum('durationAtSelectedPeriod'));

        //         return array_merge(
        //             array_values(collect($collection->first())->only(['full_name'])->toArray()),
        //             [
        //                $interval->cascade()->forHumans(['short' => true]),
        //                 round($interval->totalHours, 3),
        //                 ...$that->intervalsByDay($collection)
        //             ]
        //         );
        //     }
        // )->all();

        $result = [];
        switch ($this->report->main) {
            case EnumsUniversalReport::USER:
                if (isset($row['total_spent_time_day'])) {
                    if (isset($row['total_spent_time_day']['datasets'])) {
                        foreach ($row['total_spent_time_day']['datasets'] as $userId => $user) {
                            $resultrow = [];
                            $resultrow[] = $user['label'] ?? '';
                            $resultrow[] = '';
                            $resultrow[] = '';
                            $resultrow[] = '';
                            $resultrow[] = '';
                            $resultrow[] = '';
                            $resultrow[] = '';
                            if (isset($user['data'])) {
                                foreach ($user['data'] as $date => $time) {
                                    $resultrow[] = (string)$time;
                                }
                            }
                            $result[] = $resultrow;
                        }
                    }
                    if (isset($row['total_spent_time_day_and_tasks'])) {
                        if (isset($row['total_spent_time_day_and_tasks']['datasets'])) {
                            foreach ($row['total_spent_time_day_and_tasks']['datasets'] as $userId => $userTasks) {
                                $user = User::find($userId);
                                foreach ($userTasks as $taskId => $task) {
                                    $resultrow = [];
                                    $resultrow[] = $user->full_name;
                                    $resultrow[] = $task['label'] ?? '';
                                    $resultrow[] = '';
                                    $resultrow[] = '';
                                    $resultrow[] = '';
                                    $resultrow[] = '';
                                    $resultrow[] = '';
                                    if (isset($task['data'])) {
                                        foreach ($task['data'] as $date => $time) {
                                            $resultrow[] = (string)$time;
                                        }
                                    }
                                    $result[] = $resultrow;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($row as $user) {

                        if (isset($user['projects'])) {
                            foreach ($user['projects'] as $projectId => $project) {

                                if (isset($project['tasks'])) {
                                    foreach ($project['tasks'] as $taskId => $task) {
                                        $resultrow = [];
                                        $resultrow[] = $user['full_name'] ?? '';
                                        $resultrow[] = $project['name'] ?? '';
                                        $resultrow[] = $user['email'] ?? '';
                                        $resultrow[] = $project['created_at'] ?? '';
                                        $resultrow[] = $task['priority'] ?? '';
                                        $resultrow[] = $task['task_name'] ?? '';
                                        $resultrow[] = $task['status'] ?? '';
                                        $result[] = $resultrow;
                                    }
                                }
                            }
                        }
                        $resultrow = [];

                        $resultrow[] = '';
                        $resultrow[] = '';
                        $resultrow[] = '';
                        $resultrow[] = '';
                        $resultrow[] = '';
                        $resultrow[] = '';
                        if (isset($user['worked_time_day'])) {
                            $resultrow[] = 'Total time';
                            foreach ($user['worked_time_day'] as $date => $time)
                                $resultrow[] = $time;
                        }
                        $result[] = $resultrow;
                    }
                }

                return $result;
            case EnumsUniversalReport::TASK:
                foreach ($row as $task) {
                    if (isset($task['users'])) {
                        foreach ($task['users'] as $taskId => $taskData) {
                            $resultrow = [];
                            $resultrow[] = $taskData['full_name'] ?? '';
                            $resultrow[] = $taskData['email'] ?? '';
                            $resultrow[] = $taskData['total_spent_time_by_user'] ?? '';
                            $resultrow[] = $task['task_name'] ?? '';
                            $resultrow[] = $task['priority'] ?? '';
                            $resultrow[] = $task['status'] ?? '';
                            if (isset($taskData['workers_day'])) {
                                foreach ($taskData['workers_day'] as $date => $time) {
                                    $resultrow[] = $time;
                                }
                            }
                            $result[] = $resultrow;
                        }
                    }
                    // if (isset($task['project'])) {
                    //     foreach ($task['project'] as $projectId => $projectData) {
                    //         $resultrow = [];
                    //         $resultrow[] = $projectData['name']?? '';

                    //     }
                    //     $result[] = $resultrow;
                    // }
                }
                return $result;
            case EnumsUniversalReport::PROJECT:
                foreach ($row as $project) {
                    if (isset($project['tasks'])) {
                        foreach ($project['tasks'] as $taskId => $taskData) {
                            $resultrow = [];
                            $resultrow[] = $project['name'] ?? '';
                            // $resultrow[] = $project['created_at'] ?? '';
                            // $resultrow[] = $project['important'] ?? '';
                            // $resultrow[] = $project['priority'] ?? '';
                            // $resultrow[] = $taskData['status'] ?? '';
                            $resultrow[] = $taskData['task_name'] ?? '';
                            $result[] = $resultrow;
                        }
                    }
                    $resultrow = [];
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $result[] = $resultrow;
                    if (isset($project['users'])) {
                        foreach ($project['users'] as $userId => $userData) {
                            $resultrow = [];
                            $resultrow[] = $project['name'] ?? '';
                            // $resultrow[] = $project['created_at'] ?? '';
                            // $resultrow[] = $project['important'] ?? '';
                            // $resultrow[] = $project['priority'] ?? '';
                            $resultrow[] = $userData['full_name'] ?? '';
                            $resultrow[] = $userData['email'] ?? '';
                            $resultrow[] = $userData['total_spent_time_by_user'] ?? '';

                            if (isset($userData['workers_day'])) {
                                foreach ($userData['workers_day'] as $date => $time) {
                                    $resultrow[] = $time;
                                }
                            }
                            $result[] = $resultrow;
                        }
                    }
                    $resultrow = [];
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $resultrow[] = ' ';
                    $result[] = $resultrow;
                }
                return $result;
        }
    }

    public function sheets(): array
    {
        $sheets = [];
        $collection = $this->collectionUser()->all();
        $data = $collection['reportCharts'];

        if (isset($data['total_spent_time_day']['datasets'])) {
            foreach ($data['total_spent_time_day']['datasets'] as $userId => $user) {
                $sheets[] = new UserMultiSheetExport($collection, $userId, ($user['label'] ?? ''), $this->periodDates);
            }
        }

        // if (isset($data['total_spent_time_day_and_tasks']['datasets'])) {
        //     foreach ($data['total_spent_time_day_and_tasks']['datasets'] as $userId => $user) {

        //         $sheets[] = new ProjectMultiSheetExport($collection, $userId, ($user['label'] ?? ''), $this->periodDates);
        //     }
        // }


        return $sheets;
    }

    private function intervalsByDay(Collection $intervals): array
    {
        $intervalsByDay = [];

        foreach ($this->periodDates as $date) {
            $workedAtDate = $intervals->sum(fn ($item) => ($item->durationByDay[$date] ?? 0
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
        $result = [];
        foreach ($data['reportData'] as $user) {
            $p_id = $user?->p_id ?? null;
            $u_id = $user->u_id;
            $t_id = $user?->t_id ?? null;
            if (!array_key_exists($u_id, $result)) {
                $result[$u_id] = [];

                foreach ($user as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (array_key_exists($key, $result[$u_id]) && in_array($key, $onlyOne, true)) {
                    } elseif (in_array($key, ['p_name', 'p_created_at', 'p_description', 'p_important'], true) && !is_null($p_id)) {
                        $result[$u_id]['projects'][$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                    } elseif (in_array($key, ['t_task_name', 't_priority', 't_status', 't_due_date', 't_estimate', 't_description'], true) && !is_null($t_id)) {
                        $result[$u_id]['projects'][$p_id]['tasks'][$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$u_id]['worked_time_day'][$user->date_at] = $user->total_spent_time_by_day;
                    } elseif (!array_key_exists($key, $result[$u_id]) && in_array($key, $onlyOne, true)) {
                        $result[$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                    }
                }
            } else {
                foreach ($user as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (in_array($key, $onlyOne, true)) {
                    } elseif (in_array($key, ['p_name', 'p_created_at', 'p_description', 'p_important'], true) && !is_null($p_id)) {
                        $result[$u_id]['projects'][$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                    } elseif (in_array($key, ['t_task_name', 't_priority', 't_status', 't_due_date', 't_estimate', 't_description'], true) && !is_null($t_id)) {
                        $result[$u_id]['projects'][$p_id]['tasks'][$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$u_id]['worked_time_day'][$user->date_at] = $user->total_spent_time_by_day;
                    }
                }
            }
        }

        if (in_array('total_spent_time_by_day', $this->report->fields['calculations'])) {
            $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);
            foreach ($result as $key => $report) {
                $service->fillNullDatesAsZeroTime($result[$key]['worked_time_day']);
            }
        }
        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name,
            'reportCharts' => $data['reportCharts'],
            'periodDates' => $this->periodDates,
        ]);
    }

    public function collectionTask(): Collection
    {
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
        $result = [];
        foreach ($data['reportData'] as $task) {
            $p_id = $task->p_id;
            $u_id = $task->u_id;
            $t_id = $task->t_id;

            if (!array_key_exists($t_id, $result)) {
                $result[$t_id] = [];

                foreach ($task as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (array_key_exists($key, $result[$t_id]) && in_array($key, $onlyOne, true)) {
                    } elseif (in_array($key, ['t_task_name', 't_description', 't_due_date', 't_estimate', 't_priority', 't_status'])) {
                        $result[$t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                    } elseif (in_array($key, ['p_created_at', 'p_description', 'p_important', 'p_name',], true)) {
                        $result[$t_id]['project'][preg_replace('/p_/', '', $key, 1)] = $value;
                    } elseif (
                        in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true) && !array_key_exists('users', $result[$t_id])
                        || in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true) && !in_array($key, array_keys($result[$t_id]['users'][$u_id]))
                    ) {
                        $result[$t_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$t_id]['worked_time_day'][$task->date_at] = $task->total_spent_time_by_day;
                    } elseif ($key === 'total_spent_time_by_user_and_day') {
                        $result[$t_id]['users'][$u_id]['workers_day'][$task->date_at] = $task->total_spent_time_by_user_and_day;
                    } elseif ($key === 'total_spent_time') {
                        $result[$t_id][$key] = $task->total_spent_time;
                    } elseif ($key === 'total_spent_time_by_user') {
                        $result[$t_id]['users'][$u_id][$key] = $task->total_spent_time_by_user;
                    } elseif (!array_key_exists($key, $result[$t_id]) && in_array($key, $onlyOne, true)) {
                        $result[$t_id][$key] = $value;
                    } elseif (array_key_exists($key, $result[$p_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$t_id][$key], $value);
                    } elseif (!array_key_exists($key, $result[$t_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$t_id], [$key => $value]);
                    }
                }
            } else {
                foreach ($task as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (in_array($key, $onlyOne, true)) {
                    } elseif ($key === 'u_full_name' || $key === 'u_email' || $key === 'total_spent_time_by_user') {
                        $result[$t_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$t_id]['worked_time_day'][$task->date_at] = $task->total_spent_time_by_day;
                    } elseif ($key === 'total_spent_time_by_user_and_day') {
                        $result[$t_id]['users'][$u_id]['workers_day'][$task->date_at] = $task->total_spent_time_by_user_and_day;
                    }
                }
            }
        }

        if (
            in_array('total_spent_time_by_day', $this->report->fields['calculations'])
            || in_array('total_spent_time_by_day_and_user', $this->report->fields['calculations'])
        ) {
            $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);

            foreach ($result as $key => $report) {
                if (in_array('total_spent_time_by_day', $this->report->fields['calculations'])) {
                    $service->fillNullDatesAsZeroTime($result[$key]['worked_time_day']);
                }

                if (in_array('total_spent_time_by_day_and_user', $this->report->fields['calculations'])) {
                    foreach ($report['users'] as $k => $user) {
                        $service->fillNullDatesAsZeroTime($result[$key]['users'][$k]['workers_day']);
                    }
                }
            }
            $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);
            foreach ($result as $key => $report) {
                $service->fillNullDatesAsZeroTime($result[$key]['worked_time_day']);
            }
        }
        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name,
            'reportCharts' => $data['reportCharts'],
            'periodDates' => $this->periodDates,
        ]);
    }

    public function collectionProject(array $onlyOne, array $skipValues): Collection
    {
        $data = $this->queryReportProject();
        $result = [];
        foreach ($data['reportData'] as $project) {
            $p_id = $project->p_id;
            $u_id = $project->u_id;

            if (!array_key_exists($p_id, $result)) {
                $result[$p_id] = [];


                foreach ($project as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (array_key_exists($key, $result[$p_id]) && in_array($key, $onlyOne, true)) {
                    } elseif (in_array($key, ['t_task_name', 't_priority', 'status', 't_description', 't_due_date', 't_estimate'])) {
                        $result[$p_id]['tasks'][$project->t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                    } elseif (in_array($key, ['u_full_name', 'u_email', 'total_spent_time_by_user'], true)) {
                        $result[$p_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$p_id]['worked_time_day'][$project->date_at] = $project->total_spent_time_by_day;
                    } elseif ($key === 'total_spent_time_by_user_and_day') {
                        $result[$p_id]['users'][$u_id]['workers_day'][$project->date_at] = $project->total_spent_time_by_user_and_day;
                    } elseif ($key === 'status') {
                        $value === 'Open' ? $result[$p_id]['statuses']['open'] = true : $result[$p_id]['statuses']['closed'] = true;
                    } elseif (!array_key_exists(preg_replace('/p_/', '', $key, 1), $result[$p_id]) && in_array(preg_replace('/p_/', '', $key, 1), $onlyOne, true)) {
                        $result[$p_id][preg_replace('/p_/', '', $key, 1)] = $value;
                    } elseif (array_key_exists(preg_replace('/p_/', '', $key, 1), $result[$p_id]) && !in_array(preg_replace('/p_/', '', $key, 1), $onlyOne, true)) {
                        array_push($result[$p_id][preg_replace('/p_/', '', $key, 1)], $value);
                    } else if (!array_key_exists($key, $result[$p_id]) && !in_array($key, $onlyOne, true)) {
                        array_push($result[$p_id], [$key => $value]);
                    }
                    // status, task_name, full_name, email, total_spent_time_by_user, total_spent_time_by_user_and_day, date_at, total_spent_time_by_day *Not Only One
                    // name, created_at, description, important, priority, status, due_date, estimate,
                }
            } else {
                foreach ($project as $key => $value) {
                    if (in_array($key, $skipValues, true)) {
                    } elseif (in_array($key, $onlyOne, true)) {
                    } elseif (in_array($key, ['t_task_name', 'priority', 'status', 't_description', 't_due_date', 't_estimate'])) {
                        $result[$p_id]['tasks'][$project->t_id][preg_replace('/t_/', '', $key, 1)] = $value;
                    } elseif ($key === 'status') {
                        $value === 'Open' ? $result[$p_id]['statuses']['open'] = true : $result[$p_id]['statuses']['closed'] = true;
                    } elseif (in_array(preg_replace('/u_/', '', $key, 1), ['full_name', 'email', 'total_spent_time_by_user'])) {
                        $result[$p_id]['users'][$u_id][preg_replace('/u_/', '', $key, 1)] = $value;
                    } elseif ($key === 'total_spent_time_by_day') {
                        $result[$p_id]['worked_time_day'][$project->date_at] = $project->total_spent_time_by_day;
                    } elseif ($key === 'total_spent_time_by_user_and_day') {
                        $result[$p_id]['users'][$u_id]['workers_day'][$project->date_at] = $project->total_spent_time_by_user_and_day;
                    }
                }
            }
        }

        if (
            in_array('total_spent_time_by_day', $this->report->fields['calculations'])
            || in_array('total_spent_time_by_day_and_user', $this->report->fields['calculations'])
        ) {
            $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);

            foreach ($result as $key => $report) {
                if (in_array('total_spent_time_by_day', $this->report->fields['calculations'])) {
                    $service->fillNullDatesAsZeroTime($result[$key]['worked_time_day']);
                }

                if (in_array('total_spent_time_by_day_and_user', $this->report->fields['calculations'])) {
                    foreach ($report['users'] as $k => $user) {
                        $service->fillNullDatesAsZeroTime($result[$key]['users'][$k]['workers_day']);
                    }
                }
            }
            $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);
            foreach ($result as $key => $report) {
                $service->fillNullDatesAsZeroTime($result[$key]['worked_time_day']);
            }
        }
        return collect([
            'reportData' => $result,
            'reportName' => $this->report->name,
            'reportCharts' => $data['reportCharts'],
            'periodDates' => $this->periodDates,
        ]);
    }

    private function queryReportUser()
    {
        $sqlWith = '';
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = '';

        $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);

        $users = $service->generateSqlRaw('main', $this->report->fields['main'], 'u', 'users', 'u', '', false, true, false, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlGroupBy .= 'u.id';
        $sqlGroupBy .= ', p.id';

        $projectsUsers = $service->generateSqlRaw('projects', [], 'pu', 'projects_users', 'projects_users', 'u.id=pu.user_id', false, false, true, false);
        $sqlJoin .= $projectsUsers['sqlJoin'];

        $projects = $service->generateSqlRaw('projects', $this->report->fields['projects'], 'p', 'projects', 'projects', 'pu.project_id=p.id', false, true, true, false);
        $sqlSelect .= $projects['sqlSelect'];
        $sqlJoin .= $projects['sqlJoin'];

        if (count($this->report->fields['tasks']) > 0) {
            $sqlGroupBy .= ', t.id';
            $tasksUsers = $service->generateSqlRaw('tasks', [], 'tu', 'tasks_users', 'tasks_users', 'u.id=tu.user_id', false, false, true, false);
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

            $tasks = $service->generateSqlRaw('tasks', $cloneTasksFields, 't', 'tasks', 'tasks', 'tu.task_id=t.id AND p.id=t.project_id', false, true, true, false);
            $sqlSelect .= $tasks['sqlSelect'];
            $sqlJoin .= $tasks['sqlJoin'];
            $sqlJoin .= $afterTasksTableJoins;
        }

        $calculationsResult = $service->sqlWithForUser($this->report->fields['calculations']);
        $sqlWith .= $calculationsResult['sqlWith'];
        $sqlJoin .= $calculationsResult['sqlJoin'];
        $sqlGroupBy .= $calculationsResult['sqlGroupBy'];
        $sqlSelect .= $calculationsResult['sqlSelect'];

        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "u.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }

        $sqlWith = preg_replace("/[) ,]+$/", ') ', $sqlWith);
        $sqlSelect = rtrim($sqlSelect, ', ') . ' ';

        return [
            'reportData' => DB::select(
                "$sqlWith SELECT {$sqlSelect}
                FROM users AS u
                $sqlJoin
                WHERE $sqlWhere
                GROUP BY $sqlGroupBy"
            ),
            'reportCharts' => $service->usersCharts(),
        ];
    }


    // Таск завершён. Осталось только нормально обработать поле date_at в collectionTask UPD: время по юзерам и дням не приходит
    private function queryReportTask()
    {
        $sqlWith = '';
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = 't.id';

        $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);

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
        $tasks = $service->generateSqlRaw('main', $cloneTasksFields, 't', 'tasks', 'tasks', '', false, true, false, false);
        $sqlSelect .= $tasks['sqlSelect'];

        $tasksUsers = $service->generateSqlRaw('users', [], 'tu', 'tasks_users', 'tasks_users', 't.id=tu.task_id', false, true, true, false);
        $sqlJoin .= $tasksUsers['sqlJoin'];

        $users = $service->generateSqlRaw('users', $this->report->fields['users'], 'u', 'users', 'users', 'tu.user_id=u.id', false, true, true, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlJoin .= $users['sqlJoin'];
        $sqlGroupBy .= ', u.id';

        $projects = $service->generateSqlRaw('projects', $this->report->fields['projects'], 'p', 'projects', 'projects', 't.project_id=p.id', false, true, true, false);
        $sqlSelect .= $projects['sqlSelect'];
        $sqlJoin .= $projects['sqlJoin'];
        $sqlGroupBy .= ', p.id';

        $calculationsResult = $service->sqlWithForTask($this->report->fields['calculations']);
        $sqlWith .= $calculationsResult['sqlWith'];
        $sqlJoin .= $calculationsResult['sqlJoin'];
        $sqlGroupBy .= $calculationsResult['sqlGroupBy'];
        $sqlSelect .= $calculationsResult['sqlSelect'];



        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "t.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }

        // $sqlWith = rtrim($sqlWith, '), ').') ';
        $sqlWith = preg_replace("/[) ,]+$/", ') ', $sqlWith);
        // dd($sqlWith);
        $sqlSelect = rtrim($sqlSelect, ', ') . ' ';

        return [
            'reportData' => DB::select(
                "$sqlWith SELECT {$sqlSelect}
                FROM tasks AS t
                $sqlJoin
                WHERE $sqlWhere
                GROUP BY $sqlGroupBy"
            ),
            'reportCharts' => $service->tasksCharts(),
        ];
    }

    // Проект надо переписать группировку. Убрать зависимости join от других необязательных джойнов. Проверить правильно ли высчитывается всё время
    private function queryReportProject()
    {
        $copyTaskField = (new ArrayObject($this->report->fields['tasks']))->getArrayCopy();

        if (in_array('priority', $copyTaskField)) {
            unset($copyTaskField[array_search('priority', $copyTaskField)]);
        }

        if (in_array('status', $copyTaskField)) {
            unset($copyTaskField[array_search('status', $copyTaskField)]);
        }

        $sqlWith = "";
        $sqlSelect = '';
        $sqlJoin = '';
        $sqlWhere = '';
        $sqlGroupBy = '';
        $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);
        $projects = $service->generateSqlRaw('main', $this->report->fields['main'], 'p', 'projects', 'p', '', false, true, false, false);
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

        $tasks = $service->generateSqlRaw('tasks', $cloneTasksFields, 't', 'tasks', 'tasks', 'p.id=t.project_id', false, true, true, false);
        $sqlSelect .= $tasks['sqlSelect'];
        $sqlJoin .= $tasks['sqlJoin'];

        $tasksUsers = $service->generateSqlRaw('tasks', [], 'tu', 'tasks_users', 'tasks_users', 't.id=tu.task_id', false, true, true, false);
        // $sqlSelect .= preg_replace('/tu.id as tu_id, /', 'tu.user_id, tu.task_id, ', $tasksUsers['sqlSelect']);
        // $sqlSelect .= 'tu.user'

        $sqlJoin .= $tasksUsers['sqlJoin'];

        $users = $service->generateSqlRaw('users', $this->report->fields['users'], 'u', 'users', 'users', 'tu.user_id=u.id', false, true, true, false);
        $sqlSelect .= $users['sqlSelect'];
        $sqlJoin .= $users['sqlJoin'];
        $sqlWith .= $users['sqlWith'];

        $calculationsResult = $service->sqlWithForProject($this->report->fields['calculations']);
        $sqlWith .= $calculationsResult['sqlWith'];
        $sqlJoin .= $calculationsResult['sqlJoin'];
        $sqlGroupBy .= $calculationsResult['sqlGroupBy'];
        $sqlSelect .= $calculationsResult['sqlSelect'];

        $sqlGroupBy .= 'p.id, u.id, t.id';

        foreach ($this->report->data_objects as $key => $dataObject) {
            $sqlWhere .= "p.id=$dataObject ";
            if (++$key === count($this->report->data_objects)) {
                continue;
            }
            $sqlWhere .= "OR ";
        }

        $sqlWith = preg_replace("/[) ,]+$/", ') ', $sqlWith);
        $sqlSelect = rtrim($sqlSelect, ', ') . ' ';

        return [
            'reportData' => DB::select(
                "$sqlWith SELECT {$sqlSelect}
                FROM projects AS p
                $sqlJoin
                WHERE $sqlWhere
                GROUP BY $sqlGroupBy"
            ),
            'reportCharts' => $service->projectsCharts(),
        ];
    }
    public function headings(): array
    {
        switch ($this->report->main) {
            case EnumsUniversalReport::PROJECT:
                return [
                    'name',
                    // 'created_at',
                    // 'important',
                    // 'Task Priority',
                    // 'Task Status',
                    'Task Name/User Name',
                    'Email',
                    // 'total spent time by user',
                    // 'Hours (decimal)',
                    ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
                ];
            case EnumsUniversalReport::USER:
                return [
                    'User Name',
                    'project',
                    'email',
                    'create_at',
                    'Task Priority',
                    'Task Name',
                    'Task Status',
                    // 'Hours (decimal)',
                    ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
                ];
            case EnumsUniversalReport::TASK:
                return [
                    'User Name',
                    'email',
                    'total spent time by user',
                    'Task Name',
                    'Task Priority',
                    'Task Status',
                    // 'Hours (decimal)',
                    ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
                ];
        }
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
