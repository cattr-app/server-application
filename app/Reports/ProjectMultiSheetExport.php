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

class ProjectMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $project;
    private $projectname;
    private $countdate;
    private $periodDates;
    private $reportData;
    private $taskIdisset;
    private $userIdisset;
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
        $this->projectname = Project::find($id)->name;
        $this->reportData = $collection['reportData'];
        $this->countdate  = count($this->periodDates);
    }

    public function columnWidths(): array
    {
        $columnWidths = ['A' => 45];
        $currentColumn = 2;
        while ($currentColumn <= $this->countdate+1) {
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
                $resultrow = [];
                $resultrow[] = $project['label'] ?? '';
                $this->projectname = $project['label'] ?? '';
                if (isset($project['data'])) {
                    foreach ($project['data'] as $date => $time) {
                        $resultrow[] = number_format($time, 2, '.', '');
                    }
                }
                $result[] = $resultrow;
            }
        }
        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {
            $resultrow = [];
            $resultrow[] = 'user name';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $result[] = $resultrow;
            foreach ($this->data['total_spent_time_day_and_users_separately']['datasets'] as $projectId => $userTask) {
                if ($projectId !== $this->project)
                    continue;
                foreach ($userTask as $userId => $user) {
                    $resultrow = [];
                    $resultrow[] = $user['label'] ?? '';
                    if (isset($user['data'])) {
                        foreach ($user['data'] as $date => $time) {
                            $resultrow[] = number_format($time, 2, '.', '');
                        }
                    }
                    $result[] = $resultrow;
                }
            }
        }
        if (isset($this->reportData)) {
            foreach ($this->reportData as $projectId => $project) {
                if ($projectId !== $this->project)
                    continue;
                $resultrow = [];
                $resultrow[] = 'Project name';
                $resultrow[] = 'Create at';
                $resultrow[] = 'Description';
                $resultrow[] = 'Important';
                $result[] = $resultrow;
                $resultrow = [];
                $resultrow[] = $project['name'];
                $resultrow[] = $project['created_at'];
                $resultrow[] = $project['description'];
                $resultrow[] = $project['important'];
                $result[] = $resultrow;

                $resultrow = [];
                $resultrow[] = 'Task information';
                $resultrow[] = 'Task name';
                $resultrow[] = 'Status';
                $resultrow[] = 'Due date';
                $resultrow[] = 'Time estimat';
                $resultrow[] = 'Descriptione';
                $result[] = $resultrow;
                if (isset($project['tasks'])) {
                    foreach ($project['tasks'] as $taskId => $task) {
                        $resultrow = [];
                        $resultrow[] =  '';
                        $resultrow[] = $task['task_name'] ?? '';
                        $resultrow[] = $task['status'] ?? '';
                        $resultrow[] = $task['due_date'] ?? 'Отсутствует';
                        $resultrow[] = $task['estimate'] ?? 'Отсутствует';
                        $resultrow[] = $task['description'] ?? '';
                        $result[] = $resultrow;
                    }
                }

                if (isset($projectTasks['users'])) {
                    $resultrow = [];
                    $resultrow[] = 'User Name';
                    $resultrow[] = 'User Email';
                    $resultrow[] = 'Total time';
                    $result[] = $resultrow;
                    foreach ($projectTasks['users'] as $taskId => $user) {
                        $resultrow = [];
                        $resultrow[] = $user['full_name'] ?? '';
                        $resultrow[] = $user['email'] ?? '';
                        $resultrow[] = $user['total_spent_time_by_user'] ?? 'Отсутствует';
                        if (isset($user['workers_day'])) {
                            foreach ($user['workers_day'] as $date => $time)
                                $resultrow[] = 'Data ' . $date . ' time: ' . $time;
                        }
                        $result[] = $resultrow;
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

        $columnNumber = $this->countdate;
        $charts = [];
        $columnLast =  Coordinate::stringFromColumnIndex($columnNumber + 1);
        $rowCounts = $this->rowCount();
        if ($this->taskIdisset)
            $charts[] = $createChart(static::TEXT_USER, static::TEXT_USER, static::POSITIONS_CHART[0],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [], $columnNumber);
        if ($this->userIdisset)
            $charts[] = $createChart(static::TEXT_USER_INDIVIDUALLY , static::TEXT_USER_INDIVIDUALLY , static::POSITIONS_CHART[1],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [4, $rowCounts + 4], $columnNumber);

        return $charts;
    }
    public function headings(): array
    {
        return [
            'Project Name',
            ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }
    protected function rowCount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day']['datasets'])) {
            $this->taskIdisset = isset($this->data['total_spent_time_day']['datasets'][$this->project]);
        }
        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {
            $this->userIdisset = isset($this->data['total_spent_time_day_and_users_separately']['datasets'][$this->project]);
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
        return \Str::limit("{$this->project}) $this->projectname", 8);
    }
}
