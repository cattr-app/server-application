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
}
