<?php

namespace App\Http\Controllers\Api\Reports;

use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\ProjectReportRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Reports\ProjectReportExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Settings;
use Throwable;

class ProjectReportController
{
    /**
     * Project Report
     *
     * Retrieve detailed project report data including user tasks and time intervals.
     *
     * Required permission (any of): `report_view`, `report_full_access`
     *
     * Without `users` all users are included, without `projects` all projects.
     * Times are in seconds. `items` of a day are the intervals grouped by hour, each hour is reduced to a few
     * sample intervals.
     *
     * @response array{status: int, success: true, data: list<array{
     *     id: int,
     *     name: string,
     *     time: int,
     *     users: list<array{
     *         id: int,
     *         full_name: string,
     *         email: string,
     *         time: int,
     *         tasks: list<array{
     *             id: int,
     *             task_name: string,
     *             time: int,
     *             intervals: list<array{
     *                 date: string,
     *                 time: int,
     *                 items: list<list<array{
     *                     start_at: string,
     *                     activity_fill: int,
     *                     mouse_fill: int,
     *                     keyboard_fill: int,
     *                     end_at: string,
     *                     user_email: string,
     *                     id: int,
     *                     project_id: int,
     *                     project_name: string,
     *                     task_id: int,
     *                     task_name: string,
     *                     user_id: int,
     *                     full_name: string,
     *                     hour: int,
     *                     day: string,
     *                     minute: int|float,
     *                     duration: int,
     *                     durationByDay: array<string, int>,
     *                     durationAtSelectedPeriod: int,
     *                 }>>,
     *             }>,
     *         }>,
     *     }>,
     * }>}
     */
    public function __invoke(ProjectReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            ProjectReportExport::init(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects'),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone
            )->collection()->all(),
        )->respond();
    }

    /**
     * Download Project Report
     *
     * Downloads a project report in the specified file format.
     *
     * @throws Throwable
     */
    public function download(ProjectReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $job = new GenerateAndSendReport(
            ProjectReportExport::init(
                $request->input('users', User::all()->pluck('id')->toArray()),
                $request->input('projects'),
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
