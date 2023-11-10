<?php

namespace App\Reports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCharts;
use App\Enums\UniversalReport as EnumsUniversalReport;
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
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings
{
    private $data;
    private $user;
    private $username;
    private $report;
    private $periodDates;
    private $countresult;

    public function __construct(array $collection, $userId, $username, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->user = $userId;
        $this->username = $username;
        $this->periodDates = $periodDates;
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
        return $result;
    }
    public function charts()
    {
        $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A2', null, 1)];
        $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:H1', null, 7)];
        $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B2:H2', null, 7)];

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            [0],
            $label,
            $categories,
            $values
        );
        $plot   = new PlotArea(null, [$series]);

        $legend = new Legend();
        $chart  = new Chart('Всего отработано пользоваетелем', new Title('Всего отработано пользоваетелем'), $legend, $plot);
        $chart->setTopLeftPosition('C1');
        $chart->setTopLeftOffset(10, 10);
        $chart->setBottomRightPosition('K30');
        $chart->setBottomRightOffset(30, 30);
        $series = [];
        for ($i = 4; $i < $this->rowcount()+4; $i++) {
            $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
            $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:H1', null, 7)];
            $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B' . $i . ':H' . $i, null, 7)];


            $dataSeries = new DataSeries(
                DataSeries::TYPE_LINECHART,
                DataSeries::GROUPING_STANDARD,
                [0],
                $label,
                $categories,
                $values
            );
            $series[] = $dataSeries;
        }
        $plot   = new PlotArea(null, $series);
        $legend = new Legend();
        $chart2 = new Chart('Часов по задачам', new Title('Часов по задачам'), $legend, $plot);
        $chart2->setTopLeftPosition('C1');
        $chart2->setTopLeftOffset(10, 10);
        $chart2->setBottomRightPosition('K30');
        $chart2->setBottomRightOffset(30, 30);
        $series = [];
        for ($i = $this->rowcount() + 5; $i < ($this->rowcountproject()+$this->rowcount()+5); $i++) {
            $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
            $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:H1', null, 7)];
            $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B' . $i . ':H' . $i, null, 7)];


            $dataSeries = new DataSeries(
                DataSeries::TYPE_LINECHART,
                DataSeries::GROUPING_STANDARD,
                [0],
                $label,
                $categories,
                $values
            );
            $series[] = $dataSeries;
        }
        $plot   = new PlotArea(null, $series);
        $legend = new Legend();
        $chart3 = new Chart('Часов', new Title('Часов по проектам'), $legend, $plot);
        $chart3->setTopLeftPosition('C1');
        $chart3->setTopLeftOffset(10, 10);
        $chart3->setBottomRightPosition('K30');
        $chart3->setBottomRightOffset(30, 30);
        return [$chart, $chart2, $chart3];
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
