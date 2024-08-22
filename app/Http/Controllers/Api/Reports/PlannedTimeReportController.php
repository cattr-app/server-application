<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\PlannedTimeReportRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\Project;
use App\Reports\PlannedTimeReportExport;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Throwable;

class PlannedTimeReportController
{
    /**
     * @api             {post} /report/planned-time Planned Time Report
     * @apiDescription  Generate a report on planned tasks and associated time for a given project
     *
     * @apiVersion      4.0.0
     * @apiName         PlannedTimeReport
     * @apiGroup        Report
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   report_generate
     * @apiPermission   report_full_access
     *
     * @apiParam {Integer} id Project ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Object}   reportData                 Report data object
     * @apiSuccess {Integer}  reportData.id              Project ID
     * @apiSuccess {Integer}  reportData.company_id      Company ID
     * @apiSuccess {String}   reportData.name            Project name
     * @apiSuccess {String}   reportData.description     Project description
     * @apiSuccess {String}   reportData.deleted_at      Deleted timestamp (null if not deleted)
     * @apiSuccess {String}   reportData.created_at      Creation timestamp
     * @apiSuccess {String}   reportData.updated_at      Update timestamp
     * @apiSuccess {Boolean}  reportData.important       Whether the project is marked as important
     * @apiSuccess {String}   reportData.source          Source of the project (e.g., "internal")
     * @apiSuccess {Integer}  reportData.screenshots_state  Screenshots state (1 if active)
     * @apiSuccess {Integer}  reportData.default_priority_id Default priority ID (null if not set)
     * @apiSuccess {Integer}  reportData.total_spent_time  Total time spent on the project
     * @apiSuccess {Object[]} reportData.tasks           List of tasks under the project
     * @apiSuccess {Integer}  reportData.tasks.id        Task ID
     * @apiSuccess {String}   reportData.tasks.task_name Task name
     * @apiSuccess {String}   reportData.tasks.due_date  Task due date (null if not set)
     * @apiSuccess {String}   reportData.tasks.estimate  Estimated time for the task (null if not set)
     * @apiSuccess {Integer}  reportData.tasks.project_id Project ID to which the task belongs
     * @apiSuccess {Integer}  reportData.tasks.total_spent_time Total time spent on the task
     * @apiSuccess {Object[]} reportData.tasks.workers   List of workers assigned to the task
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "reportData": [
     *        {
     *          "id": 2,
     *          "company_id": 5,
     *          "name": "Et veniam velit tempore.",
     *          "description": "Consequatur nulla distinctio reprehenderit rerum omnis debitis. Fugit illum ratione quia harum. Optio porro consequatur enim esse.",
     *          "deleted_at": null,
     *          "created_at": "2023-10-26T10:26:42.000000Z",
     *          "updated_at": "2023-10-26T10:26:42.000000Z",
     *          "important": 1,
     *          "source": "internal",
     *          "default_priority_id": null,
     *          "screenshots_state": 1,
     *          "total_spent_time": null,
     *          "tasks": [
     *            {
     *              "id": 11,
     *              "task_name": "Qui velit fugiat magni accusantium.",
     *              "due_date": null,
     *              "estimate": null,
     *              "project_id": 2,
     *              "total_spent_time": null,
     *              "workers": []
     *            },
     *            ...
     *          ]
     *        }
     *      ]
     *
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ItemNotFoundError
     */
    public function __invoke(PlannedTimeReportRequest $request): JsonResponse
    {
        return responder()->success(
            PlannedTimeReportExport::init(
                $request->input('projects', Project::all()->pluck('id')->toArray()),
            )->collection()->all(),
        )->respond();
    }

    /**
     * @api             {post} /report/planned-time/download Download Planned Time Report
     * @apiDescription  Generate and download a report on planned time for specific projects.
     *
     * @apiVersion      4.0.0
     * @apiName         DownloadPlannedTimeReport
     * @apiGroup        Report
     *
     * @apiUse          AuthHeader
     *
     * @apiHeader {String} Accept Specifies the content type of the response. (Example: `text/csv`)
     * @apiHeader {String} Authorization Bearer token for API access. (Example: `82|LosbyrFljFDJqUcqMNG6UveCgrclt6OzTrCWdnJBEZ1fee08e6`)
     * @apiPermission   report_generate
     * @apiPermission   report_full_access
     *
     * @apiParam {Array} projects Array of project IDs to include in the report. If not provided, all projects will be included.
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "projects": [2]
     *  }
     *
     * @apiSuccess {String}   url  URL to the generated report file.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "url": "/storage/reports/0611766a-2807-4524-9add-2e8be33c3e58/PlannedTime_Report.csv"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */
    /**
     * @throws Throwable
     */
    public function download(PlannedTimeReportRequest $request): JsonResponse
    {
        $job = new GenerateAndSendReport(
            PlannedTimeReportExport::init(
                $request->input('projects', Project::all()->pluck('id')->toArray()),
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        app(Dispatcher::class)->dispatchSync($job);

        return responder()->success(['url' => $job->getPublicPath()])->respond();
    }
}
