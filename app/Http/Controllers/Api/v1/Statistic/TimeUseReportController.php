<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Property;
use App\Models\ProjectReport;
use Auth;
use Filter;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Validator;

/**
 * Class TimeUseReportController
 * @package App\Http\Controllers\Api\v1\Statistic
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
     * @var
     */
    protected $timezone;

    /**
     * ProjectReportController constructor.
     */
    public function __construct()
    {
        $companyTimezoneProperty = Property::getProperty('company', 'TIMEZONE')->first();
        $this->timezone = $companyTimezoneProperty ? $companyTimezoneProperty->getAttribute('value') : 'UTC';

        parent::__construct();
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
     * Handle the incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
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
                    $this->getEventUniqueName('answer.error.report.show'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ], 400)
            );
        }

        $user_ids = $request->input('user_ids', []);

        $timezone = $this->timezone;
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $projectReports = ProjectReport::query()
            ->select('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name',
                DB::raw("DATE(CONVERT_TZ(date, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(duration) as duration')
            )
            ->whereIn('user_id', $user_ids)
            ->whereIn('project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->where('date', '>=', $startAt)
            ->where('date', '<', $endAt)
            ->groupBy('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name')
            ->get();

        $users = [];

        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;
            $duration = (int)$projectReport->duration;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $user_id,
                    'name' => $projectReport->user_name,
                    'tasks' => [],
                    'total_time' => 0,
                ];
            }

            $users[$user_id]['tasks'][] = [
                'task_id' => $projectReport->task_id,
                'project_id' => $projectReport->project_id,
                'name' => $projectReport->task_name,
                'project_name' => $projectReport->project_name,
                'total_time' => $duration,
            ];

            $users[$user_id]['total_time'] += $duration;
        }

        $report = [['users' => array_values($users)]];

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $report
            )
        );
    }
}
