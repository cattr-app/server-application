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

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /time-duration/list Report
     * @apiDescription  Show attached users and to whom the user is attached
     *
     * @apiVersion      1.0.0
     * @apiName         Report
     * @apiGroup        Time Duration
     *
     * @apiPermission   time_duration_list
     */
}
