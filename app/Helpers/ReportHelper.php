<?php

namespace App\Helpers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JsonException;
use Maatwebsite\Excel\Excel;
use RuntimeException;

class ReportHelper
{
    /**
     * @param Project $project
     * @param TimeInterval $timeInterval
     * @param User $user
     * @param Task $task
     */
    public function __construct(
        protected Project $project,
        protected TimeInterval $timeInterval,
        protected User $user,
        protected Task $task
    ) {
    }

    /**
     * @param $collection
     *
     * @return Collection
     * @throws JsonException
     */
    public function getProcessedTimeUseReportCollection($collection): Collection
    {
        $collection = $collection->groupBy('user_id');

        $resultCollection = [];
        foreach ($collection as $userID => $items) {
            foreach ($items as $item) {
                if (!array_key_exists($userID, $resultCollection)) {
                    $resultCollection[$userID] = [
                        'total_time' => 0,
                        'user' => collect(json_decode(
                            $item->user,
                            true,
                            512,
                            JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
                        ))
                    ];
                }

                $resultCollection[$userID]['tasks'][$item->task_id] = [
                    'task_id' => $item->task_id,
                    'project_id' => $item->project_id,
                    'name' => $item->task_name,
                    'project_name' => $item->project_name,
                    'total_time' => 0,
                ];

                $intervals = collect(json_decode(
                    $item->intervals,
                    true,
                    512,
                    JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
                ));
                foreach ($intervals as $interval) {
                    $duration = Carbon::parse($interval['end_at'])->diffInSeconds($interval['start_at']);
                    $resultCollection[$userID]['tasks'][$item->task_id]['total_time'] += $duration;
                    $resultCollection[$userID]['total_time'] += $duration;
                }
            }

            // Sort User Tasks by total_time
            usort($resultCollection[$userID]['tasks'], static function ($a, $b) {
                return $a['total_time'] < $b['total_time'];
            });
        }

        // Sort Users by total_time
        usort($resultCollection, static function ($a, $b) {
            return $a['total_time'] < $b['total_time'];
        });

        return collect($resultCollection);
    }

    public static function getReportFormat(Request $request)
    {
        $format = array_flip(self::getAvailableReportFormats())[$request->header('accept')] ?? null;

        return $format === 'pdf' ? Excel::MPDF : $format;
    }

    public static function getAvailableReportFormats(): array
    {
        return [
            strtolower(Excel::CSV) => 'text/csv',
            strtolower(Excel::XLSX) => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            strtolower(Excel::XLS) => 'application/vnd.ms-excel',
            strtolower(Excel::ODS) => 'application/vnd.oasis.opendocument.spreadsheet',
            strtolower(Excel::HTML) => 'text/html',
        ];
    }

    /**
     * @param int[] $users
     * @param Carbon $startAt
     * @param Carbon $endAt
     * @param string[] $select
     * @return Builder
     */
    public static function getBaseQuery(
        array $users,
        Carbon $startAt,
        Carbon $endAt,
        array $select = []
    ): Builder {
        return Project::join('tasks', 'tasks.project_id', '=', 'projects.id')
            ->join('time_intervals', 'time_intervals.task_id', '=', 'tasks.id')
            ->join('users', 'time_intervals.user_id', '=', 'users.id')
            ->select(array_unique(
                array_merge($select, [
                    'time_intervals.id',
                    'projects.id as project_id',
                    'projects.name as project_name',
                    'tasks.id as task_id',
                    'tasks.task_name as task_name',
                    'users.id as user_id',
                    'users.full_name as user_name',
                    'time_intervals.start_at',
                ])
            ))
            ->whereBetween('time_intervals.start_at', [$startAt, $endAt])
            ->whereNull('time_intervals.deleted_at')
            ->whereIn('users.id', $users)
            ->groupBy(['tasks.id', 'users.id'])
            ->orderBy('time_intervals.start_at');
    }

    protected function getTableName(string $entity, ?string $column = null): string
    {
        if (!property_exists(static::class, $entity)) {
            throw new RuntimeException("$entity does not exists on " . static::class);
        }

        $tblName = $this->{$entity}->getTable();
        return $column ? $tblName . ".$column" : $tblName;
    }

    /**
     * @param array $uids
     * @param string $startAt
     * @param string $endAt
     * @param mixed $timezoneOffset
     *
     * @return Builder
     */
    public function getTimeUseReportQuery(
        array $uids,
        string $startAt,
        string $endAt,
        $timezoneOffset
    ): Builder {
        $projectIds = Project::all()->pluck('id');
        $query = $this->getBaseQuery($uids, $startAt, $endAt, $timezoneOffset, [
            "JSON_OBJECT(
                'id', users.id, 'full_name', users.full_name, 'email', users.email, 'company_id',
                 users.company_id, 'avatar', users.avatar
             ) as user",
            "JSON_ARRAYAGG(
                JSON_OBJECT('id', time_intervals.id, 'user_id', time_intervals.user_id, 'task_id',
                    time_intervals.task_id, 'end_at', CONVERT_TZ(time_intervals.end_at, '+00:00', ?),
                    'start_at', CONVERT_TZ(time_intervals.start_at, '+00:00', ?)
                    )
                ) as intervals"
        ], [$timezoneOffset]);
        return $query->whereIn($this->getTableName('project', 'id'), $projectIds);
    }
}
