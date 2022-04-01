<?php

namespace App\Reports;

use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectReportExport implements FromCollection, WithMapping
{
    use Exportable;

    public function __construct(
        private array $users,
        private array $projects,
        private Carbon $startAt,
        private Carbon $endAt
    ) {
    }

    public function getReport(): Collection
    {
        return $this->queryReport()->map(static function ($el) {
            $date = optional(Carbon::make($el->start_at));

            $el->hour = $date->hour;
            $el->day = $date->format('Y-m-d');
            $el->minute = round($date->minute, -1);

            return $el;
        })
            ->groupBy('project_id')->map(
                static fn(Collection $collection, int $key) => [
                    'id' => $key,
                    'name' => $collection->first()->project_name,
                    'time' => $collection->reduce(
                        static fn(
                            $acc,
                            $el
                        ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                        0
                    ),
                    'users' => $collection->groupBy('user_id')->map(
                        static fn(Collection $collection, int $key) => [
                            'id' => $key,
                            'full_name' => $collection->first()->user_name,
                            'email' => $collection->first()->user_email,
                            'time' => $collection->reduce(
                                static fn(
                                    $acc,
                                    $el
                                ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                                0
                            ),
                            'tasks' => $collection->groupBy('task_id')->map(
                                static fn(Collection $collection, int $key) => [
                                    'id' => $key,
                                    'task_name' => $collection->first()->task_name,
                                    'time' => $collection->reduce(
                                        static fn(
                                            $acc,
                                            $el
                                        ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                                        0
                                    ),
                                    'intervals' => $collection->groupBy('day')->map(
                                        static fn(Collection $collection, string $key) => [
                                            'date' => $key,
                                            'time' => $collection->reduce(
                                                static fn(
                                                    $acc,
                                                    $el
                                                ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                                                0
                                            ),
                                            'items' => $collection->groupBy('hour')->map(
                                                static fn(Collection $collection
                                                ) => $collection->groupBy('minute')->values()->first(),
                                            )->values()
                                        ],
                                    )->values(),
                                ],
                            )->values(),
                        ],
                    )->values()
                ],
            )->values();
    }

    /**
     * @param $row
     * @return array
     * @throws \Exception
     * @TODO
     */
    public function map($row): array
    {
        return array_merge(
            $row['users']->all(),
            [
                [
                    'Subtotal for ' . $row['name'],
                    CarbonInterval::seconds($row['time'])->cascade()->forHumans(),
                ]
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

    /**
     * @return Collection
     * @TODO
     */
    public function collection(): Collection
    {
        return $this->queryReport()->groupBy('project_id')->map(
            static fn($collection) => [
                'name' => $collection->first()->project_name,
                'time' => $collection->reduce(
                    static fn(
                        $acc,
                        $el
                    ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                    0
                ),
                'users' => $collection->groupBy('user_id')->map(
                    static fn ($collection) => [
                        'name' => $collection->first()->user_name,
                        'time' => $collection->reduce(
                            static fn(
                                $acc,
                                $el
                            ) => $acc + Carbon::make($el->end_at)?->diffInSeconds(Carbon::make($el->start_at)),
                            0
                        ),
                        'tasks' => $collection->sortBy('task_id'),
                    ],
                )->values(),
            ],
        )->values();
    }
}
