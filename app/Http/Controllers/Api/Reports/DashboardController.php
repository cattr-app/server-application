<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\DashboardRequest;
use App\Jobs\GenerateAndSendReport;
use App\Models\User;
use App\Reports\DashboardExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Settings;
use Throwable;

class DashboardController
{
    /**
     * Dashboard Data
     *
     * Retrieve dashboard data based on provided parameters.
     *
     * Without `users` all users are included, without `projects` all projects.
     * `data` is keyed by user ID; each value lists the time intervals of that user in the period.
     *
     * @response array{status: int, success: true, data: array<string, list<array{
     *     start_at: string,
     *     activity_fill: int,
     *     mouse_fill: int,
     *     keyboard_fill: int,
     *     end_at: string,
     *     is_manual: int,
     *     user_email: string,
     *     id: int,
     *     project_id: int,
     *     project_name: string,
     *     task_id: int,
     *     task_name: string,
     *     user_id: int,
     *     full_name: string,
     *     duration: int,
     *     from_midnight: int,
     *     durationByDay: array<string, int>,
     *     durationAtSelectedPeriod: int,
     * }>>}
     */
    public function __invoke(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects'),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone,
                $request->input('user_timezone'),
            )->collection()->all(),
        )->respond();
    }

    /**
     * Download Dashboard Report
     *
     * Generate and download a dashboard report
     *
     * Any authenticated user may call it; the data is limited by the role's scope.
     */
    public function download(DashboardRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        $job = new GenerateAndSendReport(
            DashboardExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                $request->input('projects'),
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
}
