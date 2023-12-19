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

    public function __construct(array $collection, $id, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->project = $id;
        $this->periodDates = $periodDates;
        $this->reportData = $collection['reportData'];
        $this->countdate  = count($this->periodDates);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 35,
            'B' => 35,
            'C' => 35,
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
        $columnLast =  $this->getColumnLast($columnNumber + 1);
        $columnFirst = 'B';
        $offsetChart = [10, 30];
        $positionsChart = [['A8', 'D38'], ['F8', 'J38']];
        $textUser = 'Worked by all users';
        $textUserIndividually = 'Worked by all users individually';
        $rowCounts = $this->rowCount();
        if ($this->taskIdisset)
            $charts[] = $createChart($textUser, $textUser, $positionsChart[0],  $offsetChart, $columnFirst, $columnLast, [], $columnNumber);
        if ($this->userIdisset)
            $charts[] = $createChart($textUserIndividually, $textUserIndividually, $positionsChart[1],  $offsetChart, $columnFirst, $columnLast, [4, $rowCounts + 4], $columnNumber);

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
            'Project Name',
            ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }
    protected function rowCount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day']['datasets'])) {
            $this->taskIdisset = false;
            foreach ($this->data['total_spent_time_day']['datasets'] as $projectId => $userTasks) {
                if ($projectId !== $this->project) {
                    continue;
                }
                $this->taskIdisset = true;
            }
        }
        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {
            $this->userIdisset = false;
            foreach ($this->data['total_spent_time_day_and_users_separately']['datasets'] as $projectId => $userTasks) {
                if ($projectId !== $this->project) {
                    continue;
                }
                $this->userIdisset = true;
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
        return \Str::limit("{$this->project}) $this->projectname", 25);
    }
}
