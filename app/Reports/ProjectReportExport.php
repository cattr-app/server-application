<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProjectReportExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles
{
    use Exportable;

    const PICS_AMOUNT = 6;

    private readonly CarbonPeriod $period;

    public function __construct(
        private readonly ?array $users,
        private readonly ?array $projects,
        private readonly Carbon $startAt,
        private readonly Carbon $endAt,
        private readonly string $timezone
    )
    {
        $this->period = CarbonPeriod::create($this->startAt, $this->endAt);
    }

    public function collection(): Collection
    {
        $that = $this;

        return $this->queryReport()->map(static function ($el) use ($that) {
            $date = optional(Carbon::make($el->start_at));

            $el->hour = $date->hour;
            $el->day = $date->format('Y-m-d');
            $el->minute = round($date->minute, -1);
            $el->duration = Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at));


            $startAt = Carbon::parse($el->start_at)->shiftTimezone($that->timezone);
            $endAt = Carbon::parse($el->end_at)->shiftTimezone($that->timezone);

            $startDate = $startAt->format('Y-m-d');
            $endDate = $endAt->format('Y-m-d');

            $durationByDay = [];
            if ($startDate === $endDate) {
                $durationByDay[$startDate] = $el->duration;
            } else {
//              If interval spans over midnight, divide it at midnight
                $startOfDay = $endAt->copy()->startOfDay();
                $startDateDuration = $startOfDay->diffInSeconds($startAt);

                $durationByDay[$startDate] = $startDateDuration;

                $endDateDuration = $endAt->diffInSeconds($startOfDay);

                $durationByDay[$endDate] = $endDateDuration;
            }

            $el->durationByDay = $durationByDay;

            return $el;
        })->groupBy('project_id')->map(
            static fn(Collection $collection, int $key) => [
                'id' => $key,
                'name' => $collection->first()->project_name,
                'time' => $collection->sum(fn($interval) => $that->getTotalFromDurationByDay($interval->durationByDay)),
                'users' => $collection->groupBy('user_id')->map(
                    static fn(Collection $collection, int $key) => [
                        'id' => $key,
                        'full_name' => $collection->first()->full_name,
                        'email' => $collection->first()->user_email,
                        'time' => $collection->sum(fn($interval) => (
                        $that->getTotalFromDurationByDay($interval->durationByDay)
                        )),
                        'tasks' => $collection->groupBy('task_id')->map(
                            static fn(Collection $collection, int $key) => [
                                'id' => $key,
                                'task_name' => $collection->first()->task_name,
                                'time' => $collection->sum(fn($interval) => (
                                $that->getTotalFromDurationByDay($interval->durationByDay)
                                )),
                                'intervals' => $collection->groupBy('day')->map(
                                    static fn(Collection $collection, string $key) => [
                                        'date' => $key,
                                        'time' => $collection->sum(fn($interval) => (
                                        $that->getTotalFromDurationByDay($interval->durationByDay)
                                        )),
                                        'items' => $collection->groupBy('hour')->map(
                                            static fn(Collection $collection
                                            ) => $collection
                                                ->split(self::PICS_AMOUNT)
                                                ->map(fn(Collection $group, $i
                                                ) => $i < (self::PICS_AMOUNT - 1) ? $group->first() : $group->last())
                                                ->values(),
                                        )->values(),
                                    ],
                                )->values(),
                            ],
                        )->values(),
                    ],
                )->values(),
            ],
        )->values();
    }

    /**
     *  Calculate interval duration for selected days
     * @param array $durationByDay
     * @return int
     */
    private function getTotalFromDurationByDay(array $durationByDay): int
    {
        $totalDuration = 0;
        foreach ($durationByDay as $date => $duration) {
            $this->period->contains($date) && $totalDuration += $duration;
        }
        return $totalDuration;
    }

    /**
     * @param $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        return array_merge(
            $row['users']
                ->map(static fn($collection) => $collection['tasks'])->flatten(1)
                ->map(static fn($collection) => array_merge(
                    $collection['intervals']->map(
                        static fn($collection) => $collection['items']
                    )->flatten(2)->unique(static fn($item) => $item->task_id)->map(
                        static fn($collection) => array_values($collection->only([
                            'project_name',
                            'full_name',
                            'task_name'
                        ]))
                    )->flatten(1)->all(),
                    [
                        CarbonInterval::seconds($collection['time'])->cascade()->forHumans(),
                        round(CarbonInterval::seconds($collection['time'])->totalHours, 3)
                    ]
                ))
                ->all(),
            [
                [
                    "Subtotal for {$row['name']}",
                    '',
                    '',
                    CarbonInterval::seconds($row['time'])->cascade()->forHumans(),
                    round(CarbonInterval::seconds($row['time'])->totalHours, 3),
                ],
                []
            ]
        );
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
                'users.email as user_email',
            ]
        )->whereIn('project_id', $this->projects)->get();
    }

    public function headings(): array
    {
        return [
            'Project Name',
            'User Name',
            'Task Name',
            'Hours',
            'Hours (decimal)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function getReportId(): string
    {
        return 'project_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Project_Report');
    }
}
