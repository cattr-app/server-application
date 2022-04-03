<?php

namespace App\Http\Controllers\Api\Statistic;

use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\ProjectReportRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Models\Project;
use App\Reports\ProjectReportExport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Settings;

class ProjectReportController extends ReportController
{
    public function __invoke(ProjectReportRequest $request): JsonResponse
    {
        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            (new ProjectReportExport(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects', Project::all()->pluck('id')->toArray()),
                Carbon::parse($request->input('start_at'))
                    ->setTimezone($timezone),
                Carbon::parse($request->input('end_at'))
                    ->setTimezone($timezone),
            ))->collection()
        )->respond();
    }

    public function download(ProjectReportRequest $request): JsonResponse
    {
        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        GenerateAndSendReport::dispatchAfterResponse(
            new ProjectReportExport(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects', Project::all()->pluck('id')->toArray()),
                Carbon::parse($request->input('start_at'))
                    ->setTimezone($timezone),
                Carbon::parse($request->input('end_at'))
                    ->setTimezone($timezone),
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        return responder()->success()->respond(204);
    }
    /**
     * @api             {get,post} /project-report/list List
     * @apiDescription  Get report
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Project Report
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   project_report_list
     *
     * @apiParam {Integer[]}  uids      User IDs, that must be included in the report
     * @apiParam {Integer[]}  pids      Project IDs, that must be included in the report
     * @apiParam {ISO8601}    start_at  DateTime of start
     * @apiParam {ISO8601}    end_at    DateTime of end
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "uids": [ 1 ],
     *    "pids": [ 1 ],
     *    "start_at": "2013-04-12 20:40:00",
     *    "end_at": "2013-04-12 20:41:00"
     *  }
     *
     * @apiSuccess {String}    timezone                                         Company timezone
     * @apiSuccess {Object[]}  projects                                         Response
     * @apiSuccess {Integer}   projects.id                                      Project ID
     * @apiSuccess {String}    projects.name                                    Project name
     * @apiSuccess {Integer}   projects.project_time                            Time, that has been spent on the project
     * @apiSuccess {Object[]}  projects.users                                   Users, participated in the project
     * @apiSuccess {Integer}   projects.users.id                                User ID
     * @apiSuccess {String}    projects.users.full_name                         User name
     * @apiSuccess {Object[]}  projects.users.tasks                             User tasks
     * @apiSuccess {String}    projects.users.tasks.task_name                   Task name
     * @apiSuccess {Integer}   projects.users.tasks.id                          Task ID
     * @apiSuccess {Integer}   projects.users.tasks.duration                    Task duration
     * @apiSuccess {Object[]}  projects.users.tasks.screenshots                 Screenshots of the task
     * @apiSuccess {Integer}   projects.users.tasks.screenshots.id              Screenshot ID
     * @apiSuccess {String}    projects.users.tasks.screenshots.path            Screenshot path
     * @apiSuccess {ISO8601}   projects.users.tasks.screenshots.created_at      Creation DateTime
     * @apiSuccess {String}    projects.users.tasks.screenshots.thumbnail_path  Screenshot thumbnail path
     * @apiSuccess {Object}    projects.users.tasks.dates                       Keys - dates, values - second, spent
     * @apiSuccess {Integer}   projects.users.tasks_time                        Time spent on tasks
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "projects": [
     *      {
     *        "id": "1",
     *        "name": "New name",
     *        "project_time": 275650,
     *        "users": [
     *          {
     *            "id": "2",
     *            "full_name": "asd",
     *            "tasks": [
     *              {
     *                "task_name": "ASD",
     *                "id": "2",
     *                "duration": 1490,
     *                "screenshots": {
     *                  "2020-01-23": {
     *                    "09:00": {
     *                      "4": {
     *                        "id": 6,
     *                        "path": "uploads\/screenshots\/1_2_6.png",
     *                        "created_at": "2020-01-23 09:42:26.000000",
     *                        "thumbnail_path": null
     *                      }
     *                    }
     *                  }
     *                },
     *                "dates": {
     *                  "2020-01-24": 1490
     *                }
     *              }
     *            ],
     *            "tasks_time": 1490
     *          }
     *        ]
     *      }
     *    ],
     *    "timezone": "UTC (+00:00)"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ValidationError
     */
    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /time-duration/list Days
     * @apiDescription  Get report for days
     *
     * @apiVersion      1.0.0
     * @apiName         Days
     * @apiGroup        Project Report
     *
     * @apiPermission   time_duration_list
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /project-report/projects Projects
     * @apiDescription  Get report for projects
     *
     * @apiVersion      1.0.0
     * @apiName         Projects
     * @apiGroup        Project Report
     *
     * @apiPermission   project_report_projects
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /project-report/screenshots Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         Screenshots
     * @apiGroup        Project Report
     */

    /**
     * @apiDeprecated   since 4.0.0
     * @api             {get,post} /project-report/list/tasks/{id} Task
     * @apiDescription  Get report for task
     *
     * @apiVersion      1.0.0
     * @apiName         Projects
     * @apiGroup        Project Report
     *
     * @apiPermission   project_report_task
     */
}
