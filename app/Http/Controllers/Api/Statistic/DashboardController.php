<?php

namespace App\Http\Controllers\Api\Statistic;

use Filter;
use App\Models\Project;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Auth;
use DB;
use Validator;

class DashboardController extends ReportController
{
    public static function getControllerRules(): array
    {
        return [
            'timeIntervals' => 'time-intervals.list',
            'timePerDay' => 'time-intervals.list',
        ];
    }

    public function getEventUniqueNamePart(): string
    {
        return 'dashboard';
    }

    public function timePerDay(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            return new JsonResponse([
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

        $intervals = TimeInterval::select(
            ['user_id',
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
            ->map(static function ($intervals) {
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

        return new JsonResponse($result);
    }

    /**
     * @api             {post} /v1/time-intervals/day-duration Day Duration
     * @apiDescription  Get info for dashboard summed by days
     *
     * @apiVersion      1.0.0
     * @apiName         Day Duration
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Boolean}   success                            Indicates successful request when `TRUE`
     * @apiSuccess {Object}    user_intervals                     Response, keys => requested user ids
     * @apiSuccess {Object[]}  user_intervals.intervals           Intervals info
     * @apiSuccess {Integer}   user_intervals.intervals.user_id   Intervals user ID
     * @apiSuccess {String}    user_intervals.intervals.date      Intervals date
     * @apiSuccess {Integer}   user_intervals.intervals.duration  Intervals duration
     * @apiSuccess {Integer}   user_intervals.duration            Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "user_intervals": {
     *      "2": {
     *        "intervals": [
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-23",
     *            "duration": 298
     *          },
     *          {
     *            "user_id": 2,
     *            "date": "2020-01-24",
     *            "duration": 298
     *          }
     *        ],
     *        "duration": 596
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    public function getValidationRules(): array
    {
        return [
            'user_ids' => 'exists:users,id|array',
            'project_ids' => 'nullable|exists:projects,id|array',
            'start_at' => 'date|required',
            'end_at' => 'date|required',
        ];
    }

    /**
     * @api             {post} /v1/time-intervals/dashboard Dashboard
     * @apiDescription  Get info for dashboard
     *
     * @apiVersion      1.0.0
     * @apiName         Dashboard
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  user_ids  List of user ids
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_ids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {Boolean}   success                             Indicates successful request when `TRUE`
     * @apiSuccess {Object}    userIntervals                       Response, keys => requested user ids
     * @apiSuccess {Integer}   userIntervals.user_id               ID of the user
     * @apiSuccess {Object[]}  userIntervals.intervals             Intervals info
     * @apiSuccess {Integer}   userIntervals.intervals.id          Interval ID
     * @apiSuccess {Integer}   userIntervals.intervals.user_id     Interval user ID
     * @apiSuccess {Integer}   userIntervals.intervals.task_id     Interval task ID
     * @apiSuccess {Integer}   userIntervals.intervals.project_id  Interval project ID
     * @apiSuccess {Integer}   userIntervals.intervals.duration    Interval duration
     * @apiSuccess {ISO8601}   userIntervals.intervals.start_at    DateTime of interval start
     * @apiSuccess {ISO8601}   userIntervals.intervals.end_at      DateTime of interval end
     * @apiSuccess {Integer}   userIntervals.duration              Total duration of intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "userIntervals": {
     *      "1": {
     *        "user_id": 1,
     *        "intervals": [
     *          {
     *            "id": 3261,
     *            "user_id": 1,
     *            "task_id": 1,
     *            "project_id": 1,
     *            "duration": 60,
     *            "start_at": "2013-04-12T20:40:00+00:00",
     *            "end_at": "2013-04-12T20:41:00+00:00"
     *          }
     *        ],
     *        "duration": 60
     *      }
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    /**
     * Handle the incoming request.
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
            return new JsonResponse([
                'success' => false,
                'error_type' => 'validation',
                'message' => 'Validation error',
                'info' => $validator->errors(),
            ], 400);
        }

        $user_ids = $request->input('user_ids');
        $project_ids = $request->input('project_ids');

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
            ->select(
                'i.user_id',
                'i.id',
                'i.task_id',
                't.project_id',
                'i.is_manual',
                DB::raw("CONVERT_TZ(start_at, '+00:00', '{$timezoneOffset}') as start_at"),
                DB::raw("CONVERT_TZ(end_at, '+00:00', '{$timezoneOffset}') as end_at"),
                DB::raw('TIMESTAMPDIFF(SECOND, i.start_at, i.end_at) as duration'),
                DB::raw('UNIX_TIMESTAMP(start_at) as raw_start_at'),
                DB::raw('UNIX_TIMESTAMP(end_at) as raw_end_at')
            )
            ->whereIn('i.user_id', $user_ids)
            ->where('i.start_at', '>=', $startAt)
            ->where('i.start_at', '<', $endAt)
            ->whereIn('t.project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->whereNull('i.deleted_at')
            ->orderBy('i.user_id')
            ->orderBy('i.task_id')
            ->orderBy('i.start_at');

        if (!empty($project_ids)) {
            $intervals = $intervals->whereIn('t.project_id', $project_ids);
        }

        $intervals = $intervals->get();

        $users = [];
        $previousInterval = false;
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

            $intervalData = [
                'id' => (int)$interval->id,
                'ids' => [(int)$interval->id],
                'user_id' => (int)$user_id,
                'task_id' => (int)$interval->task_id,
                'project_id' => (int)$interval->project_id,
                'is_manual' => (int)$interval->is_manual,
                'duration' => $duration,
                'start_at' => Carbon::parse($interval->start_at)->toIso8601String(),
                'end_at' => Carbon::parse($interval->end_at)->toIso8601String(),
            ];

            // Merge with the previous interval if it is consecutive and has the same task
            if ($previousInterval !== false
                && (int)$interval->raw_start_at - (int)$previousInterval->raw_end_at <= 1
                && $interval->is_manual === $previousInterval->is_manual
                && $interval->user_id === $previousInterval->user_id
                && $interval->task_id === $previousInterval->task_id) {
                $previousIndex = count($users[$user_id]['intervals']) - 1;
                $users[$user_id]['intervals'][$previousIndex]['ids'][] = $intervalData['id'];
                $users[$user_id]['intervals'][$previousIndex]['duration'] += $intervalData['duration'];
                $users[$user_id]['intervals'][$previousIndex]['end_at'] = $intervalData['end_at'];
            } else {
                $users[$user_id]['intervals'][] = $intervalData;
            }

            $users[$user_id]['duration'] += $duration;
            $previousInterval = $interval;
        }

        $results = ['userIntervals' => $users, 'success' => true];

        return new JsonResponse(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $results
            )
        );
    }
}
