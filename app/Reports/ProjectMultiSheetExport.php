<?php

namespace App\Reports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ProjectMultiSheetExport extends BaseExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $project;
    private $projectName;
    private $countDate;
    private $periodDates;
    private $reportData;
    private $showTasksChart;
    private $showUsersChart;

    const COLUMN_FIRST = 'B';
    const OFFSET_CHART = [10, 30];
    const POSITIONS_CHART = [['A8', 'D38'], ['E8', 'H38']];
    const TEXT_USER = 'Worked by all users';
    const TEXT_USER_INDIVIDUALLY = 'Worked by all users individually';

    public function __construct(array $collection, $id, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->project = $id;
        $this->periodDates = $periodDates;
        $this->projectName = Project::find($id)->name;
        $this->reportData = $collection['reportData'];
        $this->countDate  = count($this->periodDates);
    }

    public function columnWidths(): array
    {
        $columnWidths = ['A' => 45];
        $currentColumn = 2;
        while ($currentColumn <= $this->countDate + 1) {
            $columnWidths[Coordinate::stringFromColumnIndex($currentColumn)] = 25;
            $currentColumn++;
        }

        return $columnWidths;
    }

    public function array(): array
    {
        if (isset($this->data['total_spent_time_day']['datasets'])) {
            foreach ($this->data['total_spent_time_day']['datasets'] as $projectId => $project) {
                if ($projectId !== $this->project)
                    continue;

                $resultRow = [];
                $resultRow[] = $project['label'] ?? '';
                $this->projectName = $project['label'] ?? '';
                if (isset($project['data'])) {
                    foreach ($project['data'] as $date => $time) {
                        $resultRow[] = $this->formatDuration($time);
                    }
                }

                $result[] = $resultRow;
            }
        }

        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {
            $resultRow = [];
            $resultRow[] = 'user name';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $result[] = $resultRow;

            foreach ($this->data['total_spent_time_day_and_users_separately']['datasets'] as $projectId => $userTask) {
                if ($projectId !== $this->project)
                    continue;
                foreach ($userTask as $userId => $user) {
                    $resultRow = [];
                    $resultRow[] = $user['label'] ?? '';
                    if (isset($user['data'])) {
                        foreach ($user['data'] as $date => $time) {
                            $resultRow[] = $this->formatDuration($time);
                        }
                    }

                    $result[] = $resultRow;
                }
            }
        }

        if (isset($this->reportData)) {
            foreach ($this->reportData as $projectId => $project) {
                if ($projectId !== $this->project)
                    continue;
                $resultRow = [];
                $resultRow[] = 'Project name';
                $resultRow[] = 'Create at';
                $resultRow[] = 'Description';
                $resultRow[] = 'Important';
                $result[] = $resultRow;

                $resultRow = [];
                $resultRow[] = $project['name'] ?? '';
                $resultRow[] = $project['created_at'] ?? '';
                $resultRow[] = $project['description'] ?? '';
                $resultRow[] = $project['important'] ?? '';
                $result[] = $resultRow;

                $resultRow = [];
                $resultRow[] = 'Task information';
                $resultRow[] = 'Task name';
                $resultRow[] = 'Status';
                $resultRow[] = 'Due date';
                $resultRow[] = 'Time estimat';
                $resultRow[] = 'Descriptione';
                $result[] = $resultRow;

                if (isset($project['tasks'])) {
                    foreach ($project['tasks'] as $taskId => $task) {
                        $resultRow = [];
                        $resultRow[] =  '';
                        $resultRow[] = $task['task_name'] ?? '';
                        $resultRow[] = $task['status'] ?? '';
                        $resultRow[] = $task['due_date'] ?? 'Отсутствует';
                        $resultRow[] = $task['estimate'] ?? 'Отсутствует';
                        $resultRow[] = $task['description'] ?? '';
                        $result[] = $resultRow;
                    }
                }

                if (isset($projectTasks['users'])) {
                    $resultRow = [];
                    $resultRow[] = 'User Name';
                    $resultRow[] = 'User Email';
                    $resultRow[] = 'Total time';
                    $result[] = $resultRow;

                    foreach ($projectTasks['users'] as $taskId => $user) {
                        $resultRow = [];
                        $resultRow[] = $user['full_name'] ?? '';
                        $resultRow[] = $user['email'] ?? '';
                        $resultRow[] = $user['total_spent_time_by_user'] ?? 'Отсутствует';
                        if (isset($user['workers_day'])) {
                            foreach ($user['workers_day'] as $date => $time)
                                $resultRow[] = 'Data ' . $date . ' time: ' . $time;
                        }

                        $result[] = $resultRow;
                    }
                }
            }
        }

        return $result;
    }

    public function charts()
    {
        $createDataSeries = function ($label, $categories, $values) {
            return new DataSeries(
                DataSeries::TYPE_LINECHART,
                DataSeries::GROUPING_STANDARD,
                [0],
                $label,
                $categories,
                $values
            );
        };

        $createChart = function ($name, $title, $position, $offset, $startColumn, $endColumn, $rowCount, $columnLast) use ($createDataSeries) {
            $series = [];
            if (empty($rowCount)) {
                $label = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A2', null, 1)];
                $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . "!{$startColumn}1:{$endColumn}1", null, $columnLast)];
                $values = [new DataSeriesValues('Number', "'" . $this->title() . "'" . "!{$startColumn}2:{$endColumn}2", null, $columnLast)];
                $series[] = $createDataSeries($label, $categories, $values);
            } else {
                for ($i = $rowCount[0]; $i < $rowCount[1]; $i++) {
                    $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
                    $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . "!{$startColumn}1:{$endColumn}1", null,  $columnLast)];
                    $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . "!{$startColumn}" . $i . ":!{$endColumn}" . $i, null,  $columnLast)];
                    $series[] = $createDataSeries($label, $categories, $values);
                }
            }

            $plot = new PlotArea(null, $series);
            $legend = new Legend();
            $chart = new Chart($name, new Title($title), $legend, $plot);
            $chart->setTopLeftPosition($position[0]);
            $chart->setTopLeftOffset($offset[0], $offset[0]);
            $chart->setBottomRightPosition($position[1]);
            $chart->setBottomRightOffset($offset[1], $offset[1]);
            return $chart;
        };

        $columnNumber = $this->countDate;
        $charts = [];
        $columnLast =  Coordinate::stringFromColumnIndex($columnNumber + 1);
        $rowCounts = $this->rowCount();
        if ($this->showTasksChart)
            $charts[] = $createChart(static::TEXT_USER, static::TEXT_USER, static::POSITIONS_CHART[0],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [], $columnNumber);

        if ($this->showUsersChart)
            $charts[] = $createChart(static::TEXT_USER_INDIVIDUALLY, static::TEXT_USER_INDIVIDUALLY, static::POSITIONS_CHART[1],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [4, $rowCounts + 4], $columnNumber);

        return $charts;
    }

    public function headings(): array
    {
        return [
            'Project Name',
            ...collect($this->periodDates)->map(fn($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    protected function rowCount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day']['datasets'])) {
            $this->showTasksChart = isset($this->data['total_spent_time_day']['datasets'][$this->project]);
        }
        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {
            $this->showUsersChart = isset($this->data['total_spent_time_day_and_users_separately']['datasets'][$this->project]);
            foreach ($this->data['total_spent_time_day_and_users_separately']['datasets'] as $projectId => $userTasks) {
                if ($projectId !== $this->project) {
                    continue;
                }

                $count += count($userTasks);
            }
        }
        return $count;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return \Str::limit("{$this->project}) $this->projectName", 8);
    }
}
