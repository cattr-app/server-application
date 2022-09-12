<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles, WithDefaultStyles
{
    use Exportable;

    private array $periodDates;
    private string $colsDateFormat;
    private readonly CarbonPeriod $period;

    public function __construct(
        private readonly ?array               $users,
        private readonly ?array               $projects,
        private readonly Carbon               $startAt,
        private readonly Carbon               $endAt,
        private readonly string               $companyTimezone,
        private readonly string               $userTimezone,
        private readonly DashboardSortBy|null $sortBy = null,
        private readonly SortDirection|null   $sortDirection = null,
    )
    {
        $this->period = CarbonPeriod::create(
            $this->startAt->clone()->setTimezone($this->userTimezone),
            $this->endAt->clone()->setTimezone($this->userTimezone)
        );
        $this->periodDates = $this->getPeriodDates($this->period);
    }

    public function collection(): Collection
    {
        $that = $this;

        $reportCollection = $this->queryReport()->map(static function ($interval) use ($that) {
            $start = Carbon::make($interval->start_at);

            $interval->duration = Carbon::make($interval->end_at)?->diffInSeconds($start);
            $interval->from_midnight = $start?->diffInSeconds($start?->copy()->startOfDay());

            $interval->durationByDay = ReportHelper::getIntervalDurationByDay(
                $interval,
                $that->companyTimezone,
                $that->userTimezone
            );
            $interval->durationAtSelectedPeriod = ReportHelper::getIntervalDurationInPeriod(
                $that->period,
                $interval->durationByDay
            );

            return $interval;
        })->groupBy('user_id');

        if ($this->sortBy && $this->sortDirection) {
            $sortBy = match ($this->sortBy) {
                DashboardSortBy::USER_NAME => 'full_name',
                DashboardSortBy::WORKED => 'durationAtSelectedPeriod',
            };
            $sortDirection = match ($this->sortDirection) {
                SortDirection::ASC => false,
                SortDirection::DESC => true,
            };

            if ($this->sortBy === DashboardSortBy::USER_NAME) {
                $reportCollection = $reportCollection->sortBy(
                    fn($interval) => $interval[0][$sortBy],
                    SORT_NATURAL,
                    $sortDirection
                );
            } else {
                $reportCollection = $reportCollection->sortBy(
                    fn($interval) => $interval->sum($sortBy),
                    SORT_NATURAL,
                    $sortDirection
                );
            }
        }

        return $reportCollection;
    }

    /**
     * @param $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        $that = $this;
        return $row->groupBy('user_id')->map(
            static function ($collection) use ($that) {
                $interval = CarbonInterval::seconds($collection->sum('durationAtSelectedPeriod'));

                return array_merge(
                    array_values($collection->first()->only(['full_name'])),
                    [
                        $interval->cascade()->forHumans(['short' => true]),
                        round($interval->totalHours, 3),
                        ...$that->intervalsByDay($collection)
                    ]
                );
            }
        )->all();
    }

    private function intervalsByDay(Collection $intervals): array
    {
        $intervalsByDay = [];

        foreach ($this->periodDates as $date) {
            $workedAtDate = $intervals->sum(fn($item) => (
                $item->durationByDay[$date] ?? 0
            ));
            $intervalsByDay[] = round(CarbonInterval::seconds($workedAtDate)->totalHours, 3);
        }


        return $intervalsByDay;
    }

    private function queryReport(): Collection
    {
        return ReportHelper::getBaseQuery(
            $this->users,
            $this->startAt,
            $this->endAt,
            [
                'time_intervals.start_at',
                'time_intervals.activity_fill',
                'time_intervals.mouse_fill',
                'time_intervals.keyboard_fill',
                'time_intervals.end_at',
                'time_intervals.is_manual',
                'users.email as user_email',
            ]
        )->whereIn('project_id', $this->projects)->get();
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Hours',
            'Hours (decimal)',
            ...collect($this->periodDates)->map(fn($date) => Carbon::parse($date)->format('y-m-d'))
        ];
    }

    private function getPeriodDates($period): array
    {
        $dates = [];
        foreach ($period as $date) {
            $dates[] = $date->format(ReportHelper::$dateFormat);
        }
        return $dates;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]
        ];
    }

    public function getReportId(): string
    {
        return 'dashboard_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Dashboard_Report');
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
    }
}
