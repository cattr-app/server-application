<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\UniversalReport as EnumsUniversalReport;
use App\Helpers\ReportHelper;
use App\Models\UniversalReport;
use App\Models\User;
use App\Services\UniversalReportService;
use App\Services\UniversalReportServiceTask;
use App\Services\UniversalReportServiceUser;
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
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
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


class UniversalReportExport extends AppReport implements FromCollection, ShouldAutoSize, WithDefaultStyles, WithMultipleSheets
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

                // Log::error(print_r($this->collectionProject($onlyOne, $skipValues), true));
                return $this->collectionProject($onlyOne, $skipValues);

            case EnumsUniversalReport::USER:
                // Log::error(print_r($this->collectionUser(), true));
                return $this->collectionUser();

            case EnumsUniversalReport::TASK:
                // dd( $this->collectionTask());
                // Log::error(print_r($this->collectionTask(), true));
                return $this->collectionTask();
        }
    }

    public function sheets(): array
    {
        $sheets = [];
        switch ($this->report->main) {
            case EnumsUniversalReport::USER:

                $collection = $this->collectionUser()->all();
                // Log::error(print_r($collection, true));
                $data = $collection['reportCharts'];
                if (isset($data['total_spent_time_day']['datasets'])) {
                    foreach ($data['total_spent_time_day']['datasets'] as $userId => $user) {
                        $sheets[] = new UserMultiSheetExport($collection, $userId, ($user['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
            case EnumsUniversalReport::TASK:

                $collection = $this->collectionTask()->all();
                $data = $collection['reportCharts'];
                if (isset($data['total_spent_time_day']['datasets'])) {
                    foreach ($data['total_spent_time_day']['datasets'] as $taskId => $task) {
                        $sheets[] = new TaskMultiSheetExport($collection, $taskId, ($task['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
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

                $collection = $this->collectionProject($onlyOne, $skipValues)->all();
                // Log::error(print_r($collection, true));
                $data = $collection['reportCharts'];
                if (isset($data['total_spent_time_day']['datasets'])) {
                    foreach ($data['total_spent_time_day']['datasets'] as $taskId => $task) {
                        $sheets[] = new ProjectMultiSheetExport($collection, $taskId, ($task['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
        }
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
        $service2 = new UniversalReportServiceUser($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return collect([
            'reportData' => $service2->getUserReportData(),
            'reportName' => $this->report->name,
            'reportCharts' =>  $service2->getUserReportCharts(),
            'periodDates' => $this->periodDates,
        ]);
    }

    public function collectionTask(): Collection
    {
        $service2 = new UniversalReportServiceTask($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return collect([
            'reportData' => $service2->getTaskReportData(),
            'reportName' => $this->report->name,
            'reportCharts' => $service2->getTasksReportCharts(),
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


    // Таск завершён. Осталось только нормально обработать поле date_at в collectionTask UPD: время по юзерам и дням не приходит
    private function queryReportTask()
    {
        $service = new UniversalReportService($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return [

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

    private function getPeriodDates($period): array
    {
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format(ReportHelper::$dateFormat);
        }
        return $dates;
    }

    public function getReportId(): string
    {
        return 'dashboard_report';
    }

    public function getLocalizedReportName(): string
    {

        return __('Universal_Report');
    }

    public function defaultStyles(Style $defaultStyle)
    {

        return ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
    }
}
