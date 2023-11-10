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

class ProjectMultiSheetExport implements FromArray, WithTitle, WithCharts, WithHeadings
{
    private $data;
    private $user;
    private $username;
    private $report;
    private $periodDates;

    public function __construct(array $collection, $userId, $username, array $periodDates)
    {
        $this->data = $collection['reportCharts'];
        $this->user = $userId;
        $this->username = $username;
        $this->periodDates = $periodDates;
    }

    public function array(): array
    {
     
        return [];
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
        $chart  = new Chart($this->title(), new Title($this->title()), $legend, $plot);
        $chart->setTopLeftPosition('C1');
        $chart->setTopLeftOffset(10, 10);

        // Установите нижний правый угол, где график должен заканчиваться
        $chart->setBottomRightPosition('N50');
        $chart->setBottomRightOffset(50, 50);
        return $chart;
    }

    public function headings(): array
    {
       return [
            'project Name',
            // 'project',
            // 'email',
            // 'create_at',
            // 'Task Priority',
            // 'Task Name',
            // 'Task Status',
            // 'Hours (decimal)',
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
}
