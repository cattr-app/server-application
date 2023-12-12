<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\UniversalReportBase;
use App\Helpers\ReportHelper;
use App\Models\UniversalReport;
use App\Services\UniversalReportServiceProject;
use App\Services\UniversalReportServiceTask;
use App\Services\UniversalReportServiceUser;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;

class UniversalReportExport extends AppReport implements FromCollection, ShouldAutoSize, WithDefaultStyles, WithMultipleSheets
{
    use Exportable;

    private array $periodDates;
    private UniversalReport $report;
    private string $colsDateFormat;
    private readonly CarbonPeriod $period;

    public function __construct(
        private readonly int                  $id,
        private readonly Carbon               $startAt,
        private readonly Carbon               $endAt,
        private readonly string               $companyTimezone,
    ) {
        $this->report = UniversalReport::find($id);
        $this->period = CarbonPeriod::create(
            $this->startAt->clone()->setTimezone($this->companyTimezone),
            $this->endAt->clone()->setTimezone($this->companyTimezone)
        );
        $this->periodDates = $this->getPeriodDates($this->period);
    }

    public function collection(): Collection
    {
        return match ($this->report->base) {
            UniversalReportBase::PROJECT => $this->collectionProject(),
            UniversalReportBase::USER => $this->collectionUser(),
            UniversalReportBase::TASK => $this->collectionTask()
        };
    }

    public function sheets(): array
    {
        $sheets = [];
        switch ($this->report->base) {
            case UniversalReportBase::USER:
                $collection = $this->collectionUser()->all();
                $data = $collection['reportCharts'];
                if (isset($data['total_spent_time_day']['datasets'])) {
                    foreach ($data['total_spent_time_day']['datasets'] as $userId => $user) {
                        $sheets[] = new UserMultiSheetExport($collection, $userId, ($user['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
            case UniversalReportBase::TASK:

                $collection = $this->collectionTask()->all();
                $data = $collection['reportCharts'];
                if (isset($data['total_spent_time_day']['datasets'])) {
                    foreach ($data['total_spent_time_day']['datasets'] as $taskId => $task) {
                        $sheets[] = new TaskMultiSheetExport($collection, $taskId, ($task['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
            case UniversalReportBase::PROJECT:
                $collection = $this->collectionProject()->all();
                $charts = $collection['reportCharts'];
                if (isset($charts['total_spent_time_day']['datasets'])) {
                    foreach ($charts['total_spent_time_day']['datasets'] as $taskId => $task) {
                        $sheets[] = new ProjectMultiSheetExport($collection, $taskId, ($task['label'] ?? ''), $this->periodDates);
                    }
                }
                return $sheets;
        }
        return $sheets;
    }

    public function collectionUser(): Collection
    {
        $service = new UniversalReportServiceUser($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return collect([
            'reportData' => $service->getUserReportData(),
            'reportName' => $this->report->name,
            'reportCharts' =>  $service->getUserReportCharts(),
            'periodDates' => $this->periodDates,
        ]);
    }

    public function collectionTask(): Collection
    {
        $service = new UniversalReportServiceTask($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return collect([
            'reportData' => $service->getTaskReportData(),
            'reportName' => $this->report->name,
            'reportCharts' => $service->getTasksReportCharts(),
            'periodDates' => $this->periodDates,
        ]);
    }

    public function collectionProject(): Collection
    {
        $service = new UniversalReportServiceProject($this->startAt, $this->endAt, $this->report, $this->periodDates);
        return collect([
            'reportData' => $service->getProjectReportData(),
            'reportName' => $this->report->name,
            'reportCharts' =>$service->getProjectReportCharts(),
            'periodDates' => $this->periodDates,
        ]);
    }

    private function getPeriodDates($period): array
    {
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format(ReportHelper::$dateFormat);
        }
        return $dates;
    }

    public function getReportId(): string
    {
        return 'dashboard_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Universal_Report');
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
    }
}
