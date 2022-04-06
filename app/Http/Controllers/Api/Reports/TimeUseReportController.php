<?php

namespace App\Http\Controllers\Api\Reports;

use Filter;
use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Settings;

/**
 * Class TimeUseReportController
 *
 */
class TimeUseReportController extends ReportController
{
    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'time-use-report';
    }

    /**
     * TimeUseReportController constructor.
     * @param ReportHelper $reportHelper
     */
    public function __construct(protected ReportHelper $reportHelper)
    {
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'report' => 'time-use-report.list',
        ];
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
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            Filter::process(
                $this->getEventUniqueName('validation.report.show'),
                [
                    'user_ids' => 'exists:users,id|array',
                    'start_at' => 'required|date',
                    'end_at' => 'required|date',
                ]
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.report.show'),
                    [
                        'error_type' => 'validation',
                        'message' => 'Validation error',
                        'info' => $validator->errors()
                    ]
                ),
                400
            );
        }

        $user_ids = $request->input('users', []);

        $timezone = $request->input('timezone', []);

        if (!$timezone) {
            $timezone = Settings::scope('core')->get('timezone', 'UTC');
        }

        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $collection = $this->reportHelper->getTimeUseReportQuery($user_ids, $startAt, $endAt, $timezoneOffset)->get();
        $resultCollection = $this->reportHelper->getProcessedTimeUseReportCollection($collection);

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $resultCollection
            )
        );
    }
}
