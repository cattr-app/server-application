<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Models\TimeInterval;
use Auth;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Filter;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Validator;

class DashboardController extends ReportController
{
    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'user_ids' => 'exists:users,id|array',
            'start_at' => 'date|required',
            'end_at' => 'date|required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'dashboard';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'timeIntervals' => 'time-intervals.list',
            'timePerDay' => 'time-intervals.list',
        ];
    }

    /**
     * Handle the incoming request.
     *
     * todo: add api doc
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function timeIntervals(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'message' => 'Validation error',
                'info' => $validator->errors(),
            ], 400);
        }

        $user_ids = $request->input('user_ids');

        $timezone = $request->input('timezone') ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $intervals = DB::table('time_intervals AS i')
            ->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
            ->select('i.user_id', 'i.id', 'i.task_id', 't.project_id',
                DB::raw("CONVERT_TZ(start_at, '+00:00', '{$timezoneOffset}') as start_at"),
                DB::raw("CONVERT_TZ(end_at, '+00:00', '{$timezoneOffset}') as end_at"),
                DB::raw('TIMESTAMPDIFF(SECOND, i.start_at, i.end_at) as duration'))
            ->whereIn('i.user_id', $user_ids)
            ->where('i.start_at', '>=', $startAt)
            ->where('i.start_at', '<', $endAt)
            ->whereIn('t.project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->whereNull('i.deleted_at')
            ->orderBy('i.start_at')
            ->get();

        $users = [];

        foreach ($intervals as $interval) {
            $user_id = (int)$interval->user_id;
            $duration = (int)$interval->duration;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $user_id,
                    'intervals' => [],
                    'duration' => 0,
                ];
            }

            $users[$user_id]['intervals'][] = [
                'id' => (int)$interval->id,
                'user_id' => (int)$user_id,
                'task_id' => (int)$interval->task_id,
                'project_id' => (int)$interval->project_id,
                'duration' => $duration,
                'start_at' => Carbon::parse($interval->start_at)->toIso8601String(),
                'end_at' => Carbon::parse($interval->end_at)->toIso8601String(),
            ];

            $users[$user_id]['duration'] += $duration;
        }

        $results = ['userIntervals' => $users];

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $results
            )
        );
    }

    /**
     * todo: add api doc
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function timePerDay(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'message' => 'Validation error',
                'info' => $validator->errors(),
            ], 400);
        }

        $uids = $request->input('user_ids', []);

        $timezone = $request->input('timezone') ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $intervals = TimeInterval::select(['user_id',
                DB::raw("DATE(CONVERT_TZ(start_at, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as duration')]
        )
            ->whereIn('user_id', $uids)
            ->where('start_at', '>=', $startAt)
            ->where('start_at', '<', $endAt)
            ->whereNull('deleted_at')
            ->orderBy('start_at')
            ->groupBy('date')
            ->get()
            ->groupBy('user_id')
            ->map(function ($intervals) {
                $totalDuration = 0;

                foreach ($intervals as $interval) {
                    $interval->duration = (int)$interval->duration;
                    $totalDuration += $interval->duration;
                }

                return [
                    'intervals' => $intervals,
                    'duration' => $totalDuration,
                ];
            });

        $result = [
            'success' => true,
            'user_intervals' => $intervals,
        ];

        return response()->json($result);
    }
}
