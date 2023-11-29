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
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaskMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings,WithColumnWidths
{
    private $data;
    private $task;
    private $taskname;
    private $periodDates;
    private $countdate;
    private $project;

    public function __construct(array $collection, $taskId, $taskname, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->project = $collection['reportData'];
        $this->task = $taskId;
        $this->taskname = $taskname;
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
            $resultrow[] = 'task name';
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
        if (isset($this->project)) {
            $resultrow = [];
            $resultrow[] = 'task project';
            $resultrow[] = 'created at';
            $resultrow[] = ' ';
            $resultrow[] = 'description';
            $resultrow[] = ' ';
            $resultrow[] = 'important';
            $resultrow[] = ' ';
            $result[] = $resultrow;
            foreach ($this->project as $taskId => $userTasks) {

                if ($taskId !== $this->task)
                    continue;
                $resultrow = [];

                $resultrow[] = $userTasks['project']['name'] ?? '';
                $resultrow[] = $userTasks['project']['created_at'] ?? '';
                $resultrow[] =  '';
                $resultrow[] = $userTasks['project']['description'] ?? '';
                $resultrow[] =  '';
                $resultrow[] = $userTasks['project']['important'] ?? '';
                $result[] = $resultrow;
            }
        }
        return $result;
    }
    public function charts()
    {
        if ($this->countdate > 27) {
            $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A2', null, 1)];
            $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:AF1', null, $this->countdate)];
            $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B2:AF2', null,  $this->countdate)];

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
            $chart  = new Chart('Worked by all users', new Title('Worked by all users'), $legend, $plot);
            $chart->setTopLeftPosition('A8');
            $chart->setTopLeftOffset(10, 10);
            $chart->setBottomRightPosition('D38');
            $chart->setBottomRightOffset(30, 30);
            $series = [];
            for ($i = 4; $i < $this->rowcount() + 4; $i++) {
                $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
                $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:AF1', null,  $this->countdate)];
                $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B' . $i . ':AF' . $i, null,  $this->countdate)];


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
            $chart2 = new Chart('Worked by all users individually', new Title('Worked by all users individually'), $legend, $plot);
            $chart2->setTopLeftPosition('E8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('H38');
            $chart2->setBottomRightOffset(30, 30);
        } elseif ($this->countdate === 7) {
            $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A2', null, 1)];
            $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:H1', null, $this->countdate)];
            $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B2:H2', null,  $this->countdate)];

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
            $chart  = new Chart('Worked by all users', new Title('Worked by all users'), $legend, $plot);
            $chart->setTopLeftPosition('A8');
            $chart->setTopLeftOffset(10, 10);
            $chart->setBottomRightPosition('D38');
            $chart->setBottomRightOffset(30, 30);
            $series = [];
            for ($i = 4; $i < $this->rowcount() + 4; $i++) {
                $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
                $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1:H1', null,  $this->countdate)];
                $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B' . $i . ':H' . $i, null,  $this->countdate)];


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
            $chart2 = new Chart('Worked by all users individually', new Title('Worked by all users individually'), $legend, $plot);
            $chart2->setTopLeftPosition('E8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('H38');
            $chart2->setBottomRightOffset(30, 30);
        } else {
            $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A2', null, 1)];
            $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1', null, $this->countdate)];
            $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B2', null,  $this->countdate)];

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
            $chart  = new Chart('Worked by all users', new Title('Worked by all users'), $legend, $plot);
            $chart->setTopLeftPosition('A8');
            $chart->setTopLeftOffset(10, 10);
            $chart->setBottomRightPosition('D38');
            $chart->setBottomRightOffset(30, 30);
            $series = [];
            for ($i = 4; $i < $this->rowcount() + 4; $i++) {
                $label      = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!A' . $i, null, $i)];
                $categories = [new DataSeriesValues('String', "'" . $this->title() . "'" . '!B1', null,  $this->countdate)];
                $values     = [new DataSeriesValues('Number', "'" . $this->title() . "'" . '!B' . $i, null,  $this->countdate)];


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
            $chart2 = new Chart('Worked by all users individually', new Title('Worked by all users individually'), $legend, $plot);
            $chart2->setTopLeftPosition('E8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('H38');
            $chart2->setBottomRightOffset(30, 30);
        }
        return [$chart, $chart2];
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
        return  $this->taskname;
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
