<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\ProjectReportRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Models\Project;
use App\Reports\ProjectReportExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Settings;
use Throwable;

class ProjectReportController
{
    /**
     * @api             {post} /report/project Project Report
     * @apiDescription  Retrieve detailed project report data including user tasks and time intervals.
     *
     * @apiVersion      4.0.0
     * @apiName         ProjectReport
     * @apiGroup        Report
     * @apiUse          AuthHeader
     * @apiPermission   report_view
     * @apiPermission   report_full_access
     *
     * @apiParam {String}  start_at      The start date and time for the report period (ISO 8601 format).
     * @apiParam {String}  end_at        The end date and time for the report period (ISO 8601 format).
     * @apiParam {String}  user_timezone The timezone of the user making the request.
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "start_at": "2023-05-31 16:15:09",
     *    "end_at": "2023-11-30 16:20:07",
     *    "user_timezone": "Asia/Omsk"
     *  }
     *
     * @apiSuccess {Object[]}  data                                                       List of projects.
     * @apiSuccess {Integer}   data.id                                                    Project ID.
     * @apiSuccess {String}    data.name                                                  Project name.
     * @apiSuccess {Integer}   data.time                                                  Total time spent on the project.
     * @apiSuccess {Object[]}  data.users                                                 List of users associated with the project.
     * @apiSuccess {Integer}   data.users.id                                              User ID.
     * @apiSuccess {String}    data.users.full_name                                       User's full name.
     * @apiSuccess {String}    data.users.email                                           User's email address.
     * @apiSuccess {Integer}   data.users.time                                            Total time spent by the user on the project.
     * @apiSuccess {Object[]}  data.users.tasks                                           List of tasks associated with the user.
     * @apiSuccess {Integer}   data.users.tasks.id                                        Task ID.
     * @apiSuccess {String}    data.users.tasks.task_name                                 Task name.
     * @apiSuccess {Integer}   data.users.tasks.time                                      Total time spent on the task.
     * @apiSuccess {Object[]}  data.users.tasks.intervals                                 List of time intervals for the task.
     * @apiSuccess {String}    data.users.tasks.intervals.date                            Date of the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.time                            Time spent in the interval.
     * @apiSuccess {Object[]}  data.users.tasks.intervals.items                           Detailed breakdown of intervals.
     * @apiSuccess {String}    data.users.tasks.intervals.items.start_at                  Start time of the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.activity_fill             Activity fill percentage during the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.mouse_fill                Mouse activity fill percentage.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.keyboard_fill             Keyboard activity fill percentage.
     * @apiSuccess {String}    data.users.tasks.intervals.items.end_at                    End time of the interval.
     * @apiSuccess {String}    data.users.tasks.intervals.items.user_email                User's email associated with the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.id                        Interval ID.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.project_id                Project ID associated with the interval.
     * @apiSuccess {String}    data.users.tasks.intervals.items.project_name              Project name associated with the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.task_id                   Task ID associated with the interval.
     * @apiSuccess {String}    data.users.tasks.intervals.items.task_name                 Task name associated with the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.user_id                   User ID associated with the interval.
     * @apiSuccess {String}    data.users.tasks.intervals.items.full_name                 User's full name associated with the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.hour                      Hour of the day for the interval.
     * @apiSuccess {String}    data.users.tasks.intervals.items.day                       Day of the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.minute                    Minute of the hour for the interval.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.duration                  Duration of the interval.
     * @apiSuccess {Object}    data.users.tasks.intervals.items.durationByDay             Duration of the interval by day.
     * @apiSuccess {Integer}   data.users.tasks.intervals.items.durationAtSelectedPeriod  Duration at the selected period.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": [
     *      {
     *        "id": 159,
     *        "name": "Voluptas ab et ea.",
     *        "time": 3851703975,
     *        "users": [
     *          {
     *            "id": 7,
     *            "full_name": "Dr. Adaline Toy",
     *            "email": "projectManager1231@example.com",
     *            "time": 3851703975,
     *            "tasks": [
     *              {
     *                "id": 54,
     *                "task_name": "Quo consequatur mollitia nam.",
     *                "time": 550243425,
     *                "intervals": [
     *                  {
     *                    "date": "2006-05-29",
     *                    "time": 43425,
     *                    "items": [
     *                      {
     *                        "start_at": "2006-05-29 00:43:45",
     *                        "activity_fill": 87,
     *                        "mouse_fill": 81,
     *                        "keyboard_fill": 6,
     *                        "end_at": "2006-06-01 12:03:45",
     *                        "user_email": "projectManager1231@example.com",
     *                        "id": 3372,
     *                        "project_id": 159,
     *                        "project_name": "Voluptas ab et ea.",
     *                        "task_id": 54,
     *                        "task_name": "Quo consequatur mollitia nam.",
     *                        "user_id": 7,
     *                        "full_name": "Dr. Adaline Toy",
     *                        "hour": 0,
     *                        "day": "2006-05-29",
     *                        "minute": 40,
     *                        "duration": 300000,
     *                        "durationByDay": {
     *                          "2006-05-29": 256575,
     *                          "2006-06-01": 43425
     *                        },
     *                        "durationAtSelectedPeriod": 43425
     *                      }
     *                    ]
     *                  },
     *                  // More intervals...
     *                ]
     *              }
     *              // More tasks...
     *            ]
     *          }
     *          // More users...
     *        ]
     *      }
     *      // More projects...
     *    ]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */
    public function __invoke(ProjectReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            ProjectReportExport::init(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects', Project::all()->pluck('id')->toArray()),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone
            )->collection()->all(),
        )->respond();
    }

    /**
     * @api             {post} /api/report/dashboard/download Download Dashboard Report
     * @apiDescription  Downloads a dashboard report in the specified file format.
     *
     * @apiVersion      4.0.0
     * @apiName         DownloadDashboardReport
     * @apiGroup        Reports
     * @apiUse          AuthHeader
     * @apiHeader       {String} Accept         Accept mime type. Example: `text/csv`.
     *
     * @apiParam {String}   start_at            The start date and time for the report in ISO 8601 format.
     * @apiParam {String}   end_at              The end date and time for the report in ISO 8601 format.
     * @apiParam {String}   user_timezone       The timezone of the user. Example: `Asia/Omsk`.
     * @apiParam {Array}    users               List of user IDs to include in the report.
     * @apiParam {Array}    projects            List of project IDs to include in the report.
     *
     * @apiParamExample {json} Request Example:
     * {
     *   "start_at": "2023-11-01T16:15:09Z",
     *   "end_at": "2023-11-30T23:59:07Z",
     *   "user_timezone": "Asia/Omsk",
     *   "users": [7],
     *   "projects": [159]
     * }
     *
     * @apiSuccess {String}   url  The URL where the generated report can be downloaded.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": {
     *      "url": "/storage/reports/1b11d8f9-c5a3-4fe5-86bd-ae6a3031352c/Dashboard_Report.csv"
     *    }
     *  }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     * @apiUse ForbiddenError
     */
    /**
     * @throws Throwable
     */
    public function download(ProjectReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $job = new GenerateAndSendReport(
            ProjectReportExport::init(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects', Project::all()->pluck('id')->toArray()),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        app(Dispatcher::class)->dispatchSync($job);

        return responder()->success(['url' => $job->getPublicPath()])->respond();
    }

}
