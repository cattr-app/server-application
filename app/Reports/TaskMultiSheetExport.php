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

class TaskMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $task;
    private $taskname;
    private $periodDates;
    private $countdate;
    private $reportData;
    const COLUMN_FIRST = 'B';
    const OFFSET_CHART = [10, 30];
    const POSITIONS_CHART = [['A8', 'D38'], ['E8', 'H38']];
    const TEXT_USER = 'Worked by all users';
    const TEXT_USER_INDIVIDUALLY = 'Worked by all users individually';

    public function __construct(array $collection, $taskId, $taskname, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->reportData = $collection['reportData'];
        $this->task = $taskId;
        $this->taskname = $taskname;
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
            foreach ($this->data['total_spent_time_day']['datasets'] as $taskId => $task) {
                if ($taskId !== $this->task)
                    continue;
                $resultrow = [];
                $resultrow[] = $task['label'] ?? '';
                $this->taskname = $task['label'] ?? '';
                if (isset($task['data'])) {
                    foreach ($task['data'] as $date => $time) {
                        $resultrow[] = number_format($time, 2, '.', '');
                    }
                }
                $result[] = $resultrow;
            }
        }
        if (isset($this->data['total_spent_time_day_users_separately']['datasets'])) {
            $resultrow = [];
            $resultrow[] = 'User name';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $resultrow[] = ' ';
            $result[] = $resultrow;
            foreach ($this->data['total_spent_time_day_users_separately']['datasets'] as $taskId => $userTask) {
                if ($taskId !== $this->task)
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

            foreach ($this->reportData as $taskId => $userTasks) {
                if ($taskId !== $this->task)
                    continue;
                $resultrow = [];
                if (isset($userTasks['users'])) {
                    foreach ($userTasks['users'] as $taskId => $taskData) {
                        $resultrow = [];
                        $resultrow[] = 'User name';
                        $resultrow[] = 'Email user';
                        $resultrow[] = 'Total time';
                        $resultrow[] = 'Task name';
                        $resultrow[] = 'Priority';
                        $resultrow[] = 'Status';
                        $resultrow[] = 'Estimate';
                        $resultrow[] = 'Description';
                        $result[] = $resultrow;
                        $resultrow = [];
                        $resultrow[] = $taskData['full_name'] ?? '';
                        $resultrow[] = $taskData['email'] ?? '';
                        $resultrow[] = $taskData['total_spent_time_by_user'] ?? '';
                        $resultrow[] = $userTasks['task_name'] ?? '';
                        $resultrow[] = $userTasks['priority'] ?? '';
                        $resultrow[] = $userTasks['status'] ?? '';
                        $resultrow[] = $userTasks['estimate'] ?? '';
                        $resultrow[] = $userTasks['description'] ?? '';
                        if (isset($taskData['workers_day'])) {
                            foreach ($taskData['workers_day'] as $date => $time) {
                                $resultrow[] = 'Data ' . $date . ' time: ' . $time;
                            }
                        }
                        $result[] = $resultrow;
                    }
                }
                $resultrow = [];
                $resultrow[] = 'Project name';
                $resultrow[] = 'created at';
                $resultrow[] = 'description';
                $resultrow[] = 'important';
                $result[] = $resultrow;
                $resultrow = [];
                $resultrow[] = $userTasks['project']['name'] ?? '';
                $resultrow[] = $userTasks['project']['created_at'] ?? '';
                $resultrow[] = $userTasks['project']['description'] ?? '';
                $resultrow[] = $userTasks['project']['important'] ?? '';
                $result[] = $resultrow;
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
        $charts[] = $createChart(static::TEXT_USER, static::TEXT_USER, static::POSITIONS_CHART[0],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [], $columnNumber);
        $charts[] = $createChart(static::TEXT_USER_INDIVIDUALLY, static::TEXT_USER_INDIVIDUALLY, static::POSITIONS_CHART[1],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [4, $this->rowcount() + 4], $columnNumber);
        return $charts;
    }
    public function headings(): array
    {
        return [
            'Task Name',
            ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return \Str::limit("{$this->task}) $this->taskname", 8);
    }
    protected function rowcount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day_users_separately']['datasets'])) {
            foreach ($this->data['total_spent_time_day_users_separately']['datasets'] as $taskId => $userTasks) {
                if ($taskId !== $this->task)
                    continue;
                $count += count($userTasks);
            }
        }
        return $count;
    }
}
