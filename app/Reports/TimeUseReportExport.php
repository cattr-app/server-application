<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimeUseReportExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles
{
    use Exportable;

    public function __construct(
        private ?array $users,
        private Carbon $startAt,
        private Carbon $endAt
    ) {
    }

    public function collection(): Collection
    {
        return $this->queryReport()->map(static function ($el) {
            $el->duration = Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at));

            return $el;
        })->groupBy('user_id')->map(
            static fn($collection) => [
                'time' => $collection->sum('duration'),
                'user' => [
                    'id' => $collection->first()->user_id,
                    'email' => $collection->first()->user_email,
                    'full_name' => $collection->first()->user_name,
                ],
                'tasks' => $collection->groupBy('task_id')->map(
                    static fn($collection) => [
                        'time' => $collection->sum('duration'),
                        'task_id' => $collection->first()->task_id,
                        'task_name' => $collection->first()->task_name,
                        'project_id' => $collection->first()->project_id,
                        'project_name' => $collection->first()->project_name,
                    ],
                )->values(),
            ],
        )->values();
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
        )->get();
    }

    public function getReportId(): string
    {
        return 'time_use_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Time_Use_Report');
    }

    public function headings(): array
    {
        return [];
    }

    public function map($row): array
    {
        // TODO: Implement map() method.
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }
}
