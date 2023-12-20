<?php

namespace App\Reports;

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

class UserMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $reportData;
    private $user;
    private $username;
    private $periodDates;
    private $countdate;
    const COLUMN_FIRST = 'B';
    const OFFSET_CHART = [10, 30];
    const POSITIONS_CHART = [['A8', 'E38'], ['F8', 'R38'], ['S8', 'Z30']];
    const TEXT_USER = 'Worked by all users';
    const TEXT_TASK = 'Hours by tasks';
    const TEXT_PROJECT = 'Hours by projects';
    public function __construct(array $collection, $userId, $username, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->reportData =  $collection['reportData'];
        $this->user = $userId;
        $this->username = $username;
        $this->periodDates = $periodDates;
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
            foreach ($this->data['total_spent_time_day']['datasets'] as $userId => $user) {
                if ($userId !== $this->user)
                    continue;
                $resultrow = [];
                $resultrow[] = $user['label'] ?? '';
                $this->username = $user['label'] ?? '';
                if (isset($user['data'])) {
                    foreach ($user['data'] as $date => $time) {
                        $resultrow[] = number_format($time, 2, '.', '');
                    }
                }
                $result[] = $resultrow;
            }
        }

        if (isset($this->data['total_spent_time_day_and_tasks']['datasets'])) {
            $resultrow = [];
            $resultrow[] = 'task name';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $result[] = $resultrow;
            foreach ($this->data['total_spent_time_day_and_tasks']['datasets'] as $userId => $userTasks) {
                if ($userId !== $this->user)
                    continue;
                foreach ($userTasks as $taskId => $task) {
                    $resultrow = [];
                    $resultrow[] = $task['label'] ?? '';
                    if (isset($task['data'])) {
                        foreach ($task['data'] as $date => $time) {
                            $resultrow[] = number_format($time, 2, '.', '');
                        }
                    }
                    $result[] = $resultrow;
                }
            }
        }
        if (isset($this->data['total_spent_time_day_and_projects']['datasets'])) {
            $resultrow = [];
            $resultrow[] = 'task project';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $result[] = $resultrow;
            foreach ($this->data['total_spent_time_day_and_projects']['datasets'] as $userId => $userTasks) {
                if ($userId !== $this->user)
                    continue;
                foreach ($userTasks as $taskId => $task) {
                    $resultrow = [];
                    $resultrow[] = $task['label'] ?? '';
                    if (isset($task['data'])) {
                        foreach ($task['data'] as $date => $time) {
                            $resultrow[] = number_format($time, 2, '.', '');
                        }
                    }
                    $result[] = $resultrow;
                }
            }
        }
        if (isset($this->reportData)) {
            $resultrow = [];
            $resultrow[] = 'User Name';
            $resultrow[] = 'User Email';
            $resultrow[] = 'Project Name';
            $resultrow[] = 'Project created at';
            $resultrow[] = 'Task name';
            $resultrow[] = 'Task priority';
            $resultrow[] = 'Task status';
            $resultrow[] = 'Task due date';
            $resultrow[] = 'Task description';
            $result[] = $resultrow;
            foreach ($this->reportData as $userId => $user) {

                if ($userId !== $this->user)
                    continue;
                if (isset($user['projects'])) {
                    foreach ($user['projects'] as $projectId => $project) {

                        if (isset($project['tasks'])) {
                            foreach ($project['tasks'] as $taskId => $task) {
                                $resultrow = [];
                                $resultrow[] = $user['full_name'] ?? '';
                                $resultrow[] = $user['email'] ?? '';
                                $resultrow[] = $project['name'] ?? '';
                                $resultrow[] = $project['created_at'] ?? '';
                                $resultrow[] = $task['task_name'] ?? '';
                                $resultrow[] = $task['priority'] ?? '';
                                $resultrow[] = $task['status'] ?? '';
                                $resultrow[] = $task['due_date'] ?? '';
                                $resultrow[] = $task['description'] ?? '';
                                $result[] = $resultrow;
                            }
                        }
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
        $charts[] = $createChart(static::TEXT_USER, static::TEXT_USER, static::POSITIONS_CHART[0],   static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [], $columnNumber);
        $charts[] = $createChart(static::TEXT_TASK, static::TEXT_TASK, static::POSITIONS_CHART[1],   static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [4, $this->rowcount() + 4], $columnNumber);
        $charts[] = $createChart(static::TEXT_PROJECT, static::TEXT_PROJECT, static::POSITIONS_CHART[2],    static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [$this->rowcount() + 5, $this->rowcountproject() + $this->rowcount() + 5], $columnNumber);
        return $charts;
    }
    public function headings(): array
    {
        return [
            'User Name',
            ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return \Str::limit("$this->username", 10);
    }
    protected function rowcount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day_and_tasks']['datasets'])) {

            foreach ($this->data['total_spent_time_day_and_tasks']['datasets'] as $userId => $userTasks) {
                if ($userId !== $this->user)
                    continue;
                $count += count($userTasks);
            }
        }
        return $count;
    }
    protected function rowcountproject()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day_and_projects']['datasets'])) {

            foreach ($this->data['total_spent_time_day_and_projects']['datasets'] as $userId => $userTasks) {
                if ($userId !== $this->user)
                    continue;
                $count += count($userTasks);
            }
        }
        return $count;
    }
}
