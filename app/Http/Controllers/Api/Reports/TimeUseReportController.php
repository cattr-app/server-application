<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Requests\Reports\TimeUseReportRequest;
use App\Models\User;
use App\Reports\TimeUseReportExport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Settings;

/**
 * Class TimeUseReportController
 *
 */
class TimeUseReportController
{
    /**
     * Get User Time Report
     *
     * Retrieves the time report for specified users within a given time range.
     *
     * Without `users` all users are included. Times are in seconds.
     *
     * @response array{status: int, success: true, data: list<array{
     *     time: int,
     *     user: array{id: int, email: string, full_name: string},
     *     tasks: list<array{time: int, task_id: int, task_name: string, project_id: int, project_name: string}>,
     * }>}
     */
    public function __invoke(TimeUseReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            TimeUseReportExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone
            )->collection()->all(),
        )->respond();
    }
}
