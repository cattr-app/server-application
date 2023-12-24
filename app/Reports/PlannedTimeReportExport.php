<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Models\Project;
use App\Models\CronTaskWorkers;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Settings;

class PlannedTimeReportExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly ?array $projects,
    ) {
    }

    public function collection(): Collection
    {
        return collect([
            'reportData' => $this->queryReport(),
            'reportDate' => Settings::scope('core.reports')->get('planned_time_report_date', null)
        ]);
    }


    /**
     * @param Collection|string $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        if (is_string($row)) {
            return [
                [],
                ['', "Created at $row", '', '', '']
            ];
        }

        $reportArr = [];

        foreach ($row->toArray() as $project) {
            foreach ($project['tasks'] as $task) {
                foreach ($task['workers'] as $worker) {
                    $reportArr[] = [
                        $project['name'],
                        $task['task_name'],
                        $worker['user']['full_name'],
                        CarbonInterval::seconds($worker['duration'])->cascade()->forHumans(),
                        round(CarbonInterval::seconds($worker['duration'])->totalHours, 3)
                    ];
                }
                if ($task['estimate'] > 0) {
                    $reportArr[] = [
                        [],
                        "Estimate for {$task['task_name']}",
                        '',
                        CarbonInterval::seconds($task['estimate'])->cascade()->forHumans(),
                        round(CarbonInterval::seconds($task['estimate'])->totalHours, 3),
                    ];
                }
                if (count($task['workers']) > 1) {
                    $reportArr[] = [
                        [],
                        "Subtotal for {$task['task_name']}",
                        '',
                        CarbonInterval::seconds($task['total_spent_time'])->cascade()->forHumans(),
                        round(CarbonInterval::seconds($task['total_spent_time'])->totalHours, 3),
                    ];
                    $reportArr[] = [];
                }
            }
            if ($project['total_spent_time'] > 0) {
                $reportArr[] = [
                    "Subtotal for {$project['name']}",
                    '',
                    '',
                    CarbonInterval::seconds($project['total_spent_time'])->cascade()->forHumans(),
                    round(CarbonInterval::seconds($project['total_spent_time'])->totalHours, 3),
                ];
                $reportArr[] = [];
                $reportArr[] = [];
            }
        }

        return $reportArr;
    }

    private function queryReport(): Collection
    {
        $taskWorkersTable = (new CronTaskWorkers())->table;
        return Project::with(
            [
                'tasks' => static function (HasMany $query) use ($taskWorkersTable) {
                    $query->select('id', 'task_name', 'due_date', 'estimate', 'project_id')
                        ->withSum(['workers as total_spent_time' => static function (EloquentBuilder $query) use ($taskWorkersTable) {
                            $query->where("$taskWorkersTable.created_by_cron", true);
                        }], 'duration')
                        ->withCasts(['total_spent_time' => 'integer'])
                        ->orderBy('total_spent_time', 'desc');
                },
                'tasks.workers' => static function (HasMany $query) use ($taskWorkersTable) {
                    $query->select('id', 'user_id', 'task_id', 'duration')
                        ->where("$taskWorkersTable.created_by_cron", true);
                },
                'tasks.workers.user:id,full_name,email',
            ]
        )
            ->withSum(['workers as total_spent_time' => static function (EloquentBuilder $query) use ($taskWorkersTable) {
                $query->where("$taskWorkersTable.created_by_cron", true);
            }], 'duration')
            ->withCasts(['total_spent_time' => 'integer'])
            ->whereIn('id', $this->projects)->get();
    }

    public function headings(): array
    {
        return [
            'Project Name',
            'Task Name',
            'User Name',
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
        return 'planned-time_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('PlannedTime_Report');
    }
}
