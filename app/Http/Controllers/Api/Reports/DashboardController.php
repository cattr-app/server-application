<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\DashboardRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\Project;
use App\Models\User;
use App\Reports\DashboardExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Settings;
use Throwable;

class DashboardController
{
    public function __invoke(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects') ?? Project::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone,
                $request->input('user_timezone'),
            )->collection()->all(),
        )->respond();
    }

    /**
     * @throws Throwable
     */
    public function download(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $job = new GenerateAndSendReport(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects') ?? Project::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone,
                $request->input('user_timezone'),
                DashboardSortBy::tryFrom($request->input('sort_column')),
                SortDirection::tryFrom($request->input('sort_direction')),
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        app(Dispatcher::class)->dispatchSync($job);

        return responder()->success(['url' => $job->getPublicPath()])->respond();
    }

    /**
     * @api             {post} /time-intervals/day-duration Day Duration
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

    /**
     * @api             {post} /time-intervals/dashboard Dashboard
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
}
