<?php

namespace App\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use App\Enums\UniversalReportBase;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $reportData;
    private $user;
    private $username;
    private $periodDates;
    private $countdate;
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
        return [
            'A' => 55,
            'B' => 25,
            'C' => 25,
            'D' => 25,
            'E' => 25,
            'F' => 25,
            'G' => 25,
            'H' => 25,
            'I' => 25,
            'J' => 25,
        ];
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
        $columnLast =  $this->getColumnLast($columnNumber + 1);
        $columnFirst = 'B';
        $offsetChart = [10, 30];
        $positionsChart = [['A8', 'E38'], ['F8', 'R38'], ['S8', 'Z30']];
        $textUser = 'Total time worked by the user';
        $textTask = 'Hours by tasks';
        $textProject = 'Hours by projects';
        $charts[] = $createChart($textUser, $textUser, $positionsChart[0],  $offsetChart, $columnFirst, $columnLast, [], $columnNumber);
        $charts[] = $createChart($textTask, $textTask, $positionsChart[1],  $offsetChart, $columnFirst, $columnLast, [4, $this->rowcount() + 4], $columnNumber);
        $charts[] = $createChart($textProject, $textProject, $positionsChart[2],  $offsetChart, $columnFirst, $columnLast, [$this->rowcount() + 5, $this->rowcountproject() + $this->rowcount() + 5], $columnNumber);
        return $charts;
    }
    function getColumnLast($columnNumber)
    {
        $columnName = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $columnName = chr(65 + $remainder) . $columnName;
            $columnNumber = intdiv(($columnNumber - $remainder - 1), 26);
        }
        return $columnName;
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
        return  $this->username;
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
