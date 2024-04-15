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
    public function __invoke(PlannedTimeReportRequest $request): JsonResponse
    {
        return responder()->success(
            PlannedTimeReportExport::init(
                $request->input('projects', Project::all()->pluck('id')->toArray()),
            )->collection()->all(),
        )->respond();
    }

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
