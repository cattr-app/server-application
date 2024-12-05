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

class TaskMultiSheetExport extends BaseExport implements FromArray, WithTitle, WithCharts, WithHeadings, WithColumnWidths
{
    private $data;
    private $task;
    private $taskName;
    private $periodDates;
    private $countDate;
    private $reportData;

    const COLUMN_FIRST = 'B';
    const OFFSET_CHART = [10, 30];
    const POSITIONS_CHART = [['A8', 'D38'], ['E8', 'H38']];
    const TEXT_USER = 'Worked by all users';
    const TEXT_USER_INDIVIDUALLY = 'Worked by all users individually';

    public function __construct(array $collection, $taskId, $taskName, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->reportData = $collection['reportData'];
        $this->task = $taskId;
        $this->taskName = $taskName;
        $this->periodDates = $periodDates;
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
            foreach ($this->data['total_spent_time_day']['datasets'] as $taskId => $task) {
                if ($taskId !== $this->task)
                    continue;

                $resultRow = [];
                $resultRow[] = $task['label'] ?? '';
                $this->taskName = $task['label'] ?? '';
                if (isset($task['data'])) {
                    foreach ($task['data'] as $date => $time) {
                        $resultRow[] = $this->formatDuration($time);
                    }
                }

                $result[] = $resultRow;
            }
        }
        if (isset($this->data['total_spent_time_day_users_separately']['datasets'])) {
            $resultRow = [];
            $resultRow[] = 'User name';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $resultRow[] = ' ';
            $result[] = $resultRow;

            foreach ($this->data['total_spent_time_day_users_separately']['datasets'] as $taskId => $userTask) {
                if ($taskId !== $this->task)
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

            foreach ($this->reportData as $taskId => $userTasks) {
                if ($taskId !== $this->task)
                    continue;
                $resultRow = [];
                if (isset($userTasks['users'])) {
                    foreach ($userTasks['users'] as $taskId => $taskData) {
                        $resultRow = [];
                        $resultRow[] = 'User name';
                        $resultRow[] = 'Email user';
                        $resultRow[] = 'Total time';
                        $resultRow[] = 'Task name';
                        $resultRow[] = 'Priority';
                        $resultRow[] = 'Status';
                        $resultRow[] = 'Estimate';
                        $resultRow[] = 'Description';
                        $result[] = $resultRow;

                        $resultRow = [];
                        $resultRow[] = $taskData['full_name'] ?? '';
                        $resultRow[] = $taskData['email'] ?? '';
                        $resultRow[] = $taskData['total_spent_time_by_user'] ?? '';
                        $resultRow[] = $userTasks['task_name'] ?? '';
                        $resultRow[] = $userTasks['priority'] ?? '';
                        $resultRow[] = $userTasks['status'] ?? '';
                        $resultRow[] = $userTasks['estimate'] ?? '';
                        $resultRow[] = $userTasks['description'] ?? '';
                        if (isset($taskData['workers_day'])) {
                            foreach ($taskData['workers_day'] as $date => $time) {
                                $resultRow[] = 'Data ' . $date . ' time: ' . $this->formatDuration($time);
                            }
                        }

                        $result[] = $resultRow;
                    }
                }

                $resultRow = [];
                $resultRow[] = 'Project name';
                $resultRow[] = 'created at';
                $resultRow[] = 'description';
                $resultRow[] = 'important';
                $result[] = $resultRow;

                $resultRow = [];
                $resultRow[] = $userTasks['project']['name'] ?? '';
                $resultRow[] = $userTasks['project']['created_at'] ?? '';
                $resultRow[] = $userTasks['project']['description'] ?? '';
                $resultRow[] = $userTasks['project']['important'] ?? '';
                $result[] = $resultRow;
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
        $charts[] = $createChart(static::TEXT_USER, static::TEXT_USER, static::POSITIONS_CHART[0],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [], $columnNumber);
        $charts[] = $createChart(static::TEXT_USER_INDIVIDUALLY, static::TEXT_USER_INDIVIDUALLY, static::POSITIONS_CHART[1],  static::OFFSET_CHART, static::COLUMN_FIRST, $columnLast, [4, $this->rowCount() + 4], $columnNumber);
        return $charts;
    }
    public function headings(): array
    {
        return [
            'Task Name',
            ...collect($this->periodDates)->map(fn($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return \Str::limit("{$this->task}) $this->taskName", 8);
    }

    protected function rowCount()
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
