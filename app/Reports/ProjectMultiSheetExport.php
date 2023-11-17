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

    public function __construct(array $collection, $userId, $username, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->project = $userId;
        $this->projectname = $username;
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

            foreach ($this->reportData as $projectId => $projectTasks) {

                if ($projectId !== $this->project)
                    continue;
                $resultrow = [];
                $resultrow[] = 'Project name';
                $resultrow[] = 'Create at';
                $resultrow[] = 'Description';
                $resultrow[] = 'Important';
                $resultrow[] = 'Priority';
                $result[] = $resultrow;
                $resultrow = [];
                $resultrow[] = $projectTasks['name'];
                $resultrow[] = $projectTasks['created_at'];
                $resultrow[] = $projectTasks['description'];
                $resultrow[] = $projectTasks['important'];
                $resultrow[] = $projectTasks['priority'];
                $result[] = $resultrow;

                $resultrow = [];
                $resultrow[] = 'Task information';
                $resultrow[] = 'Task name';
                $resultrow[] = 'Status';
                $resultrow[] = 'Due date';
                $resultrow[] = 'Time estimat';
                $resultrow[] = 'Descriptione';
                $result[] = $resultrow;
                if (isset($projectTasks['tasks'])) {
                    foreach ($projectTasks['tasks'] as $taskId => $task) {


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
            $chart2->setTopLeftPosition('F8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('J38');
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
            $chart2->setTopLeftPosition('F8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('J38');
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
            $chart2->setTopLeftPosition('F8');
            $chart2->setTopLeftOffset(10, 10);
            $chart2->setBottomRightPosition('J38');
            $chart2->setBottomRightOffset(30, 30);
        }
        return [$chart, $chart2];
    }

    public function headings(): array
    {

        return [
            'Project Name',
            ...collect($this->periodDates)->map(fn ($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }
    protected function rowcount()
    {
        $count = 0;
        if (isset($this->data['total_spent_time_day_and_users_separately']['datasets'])) {

            foreach ($this->data['total_spent_time_day_and_users_separately']['datasets'] as $projectId => $userTasks) {
                if ($projectId !== $this->project)
                    continue;
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
        return  $this->projectname;
    }
}
