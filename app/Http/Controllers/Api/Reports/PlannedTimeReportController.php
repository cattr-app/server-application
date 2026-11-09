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
     * Planned Time Report
     *
     * Generate a report on planned tasks and associated time for a given project
     *
     * Required permission (any of): `report_generate`, `report_full_access`
     *
     * Without `projects` all projects are included.
     * `reportDate` is the time when the worked time of the tasks was last calculated (`null` if it never was).
     * Times are in seconds.
     *
     * @response array{status: int, success: true, data: array{
     *     reportData: list<array{
     *         id: int,
     *         company_id: int,
     *         name: string,
     *         description: string|null,
     *         deleted_at: string|null,
     *         created_at: string|null,
     *         updated_at: string|null,
     *         important: int,
     *         source: string,
     *         default_priority_id: int|null,
     *         group: int|null,
     *         screenshots_state: int,
     *         total_spent_time: int|null,
     *         tasks: list<array{
     *             id: int,
     *             task_name: string,
     *             due_date: string|null,
     *             estimate: int|null,
     *             project_id: int,
     *             total_spent_time: int|null,
     *             workers: list<array{
     *                 id: int,
     *                 user_id: int,
     *                 task_id: int,
     *                 duration: int,
     *                 user: array{id: int, full_name: string, email: string},
     *             }>,
     *         }>,
     *     }>,
     *     reportDate: string|null,
     * }}
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
     * Download Planned Time Report
     *
     * Generate and download a report on planned time for specific projects.
     *
     * Required permission (any of): `report_generate`, `report_full_access`
     *
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
