<?php

namespace App\Helpers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JsonException;
use Maatwebsite\Excel\Excel;
use RuntimeException;

class ReportHelper
{
    public static string $dateFormat = 'Y-m-d';

    public static function getReportFormat(Request $request)
    {
        return array_flip(self::getAvailableReportFormats())[$request->header('accept')] ?? null;
    }

    public static function getAvailableReportFormats(): array
    {
        return [
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pdf' => 'application/pdf',
            'xls' => 'application/vnd.ms-excel',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'html' => 'text/html',
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
        array  $users,
        Carbon $startAt,
        Carbon $endAt,
        array  $select = []
    ): Builder
    {
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
                    'users.full_name as full_name',
                    'time_intervals.start_at',
                ])
            ))
            ->where(fn($query) => $query->whereBetween('time_intervals.start_at', [$startAt, $endAt])
                ->orWhereBetween('time_intervals.end_at', [$startAt, $endAt]))
            ->whereNull('time_intervals.deleted_at')
            ->whereIn('users.id', $users)
            ->groupBy(['tasks.id', 'users.id', 'time_intervals.start_at'])
            ->orderBy('time_intervals.start_at');
    }

    /**
     * Calculate interval duration in period
     * @param CarbonPeriod $period
     * @param array $durationByDay
     * @return int
     */
    public static function getIntervalDurationInPeriod(CarbonPeriod $period, array $durationByDay): int
    {
        $durationInPeriod = 0;
        foreach ($durationByDay as $date => $duration) {
            $period->contains($date) && $durationInPeriod += $duration;
        }
        return $durationInPeriod;
    }

    /**
     * Splits interval by days on which it exists on. Considering timezone of a user if provided.
     * @param $interval
     * @param $companyTimezone
     * @param $userTimezone
     * @return array
     */
    public static function getIntervalDurationByDay($interval, $companyTimezone, $userTimezone = null): array
    {
        if ($userTimezone === null) {
            $userTimezone = $companyTimezone;
        }

        $startAt = Carbon::parse($interval->start_at)->shiftTimezone($companyTimezone)->setTimezone($userTimezone);
        $endAt = Carbon::parse($interval->end_at)->shiftTimezone($companyTimezone)->setTimezone($userTimezone);

        $startDate = $startAt->format(self::$dateFormat);
        $endDate = $endAt->format(self::$dateFormat);

        $durationByDay = [];
        if ($startDate === $endDate) {
            $durationByDay[$startDate] = $interval->duration;
        } else {
//              If interval spans over midnight, divide it at midnight
            $startOfDay = $endAt->copy()->startOfDay();
            $startDateDuration = $startOfDay->diffInSeconds($startAt);

            $durationByDay[$startDate] = $startDateDuration;

            $endDateDuration = $endAt->diffInSeconds($startOfDay);

            $durationByDay[$endDate] = $endDateDuration;
        }
        return $durationByDay;
    }
}
