<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\EventFilter\Facades\Filter;
use App\Helpers\ReportHelper;
use App\Models\Project;
use App\Models\ProjectReport;
use App\Models\Property;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectReportController extends ReportController
{
    /**
     * @var
     */
    protected $timezone;
    /**
     * @var ReportHelper
     */
    protected $reportHelper;

    /**
     * ProjectReportController constructor.
     *
     * @param  ReportHelper  $reportHelper
     */
    public function __construct(
        ReportHelper $reportHelper
    ) {
        $companyTimezoneProperty = Property::getProperty(Property::COMPANY_CODE, 'TIMEZONE')->first();
        $this->timezone = $companyTimezoneProperty ? $companyTimezoneProperty->getAttribute('value') : 'UTC';
        $this->reportHelper = $reportHelper;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'uids' => 'exists:users,id|array',
            'pids' => 'exists:projects,id|array',
            'start_at' => 'required|date',
            'end_at' => 'required|date',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'project-report';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'report' => 'project-report.list',
            'projects' => 'project-report.projects',
            'task' => 'project-report.list',
            'days' => 'time-duration.list',
            'screenshots' => 'project-report.screenshots'
        ];
    }

    /**
     * @api             {get,post} /v1/project-report/list List
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
     * @param  Request  $request
     * @return array|JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            Filter::process(
                $this->getEventUniqueName('validation.report.show'),
                $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.report.show'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $uids = $request->input('uids', []);
        $pids = $request->input('pids', []);

        $timezone = $this->timezone;
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $pids = $pids ?? Project::getUserRelatedProjectIds(request()->user());


        $collection = $this->reportHelper->getProjectReportQuery($uids, $pids, $startAt, $endAt, $timezoneOffset)->get();
        $resultCollection = $this->reportHelper->getProcessedProjectReportCollection($collection);

        $result = [
            'projects' => $resultCollection,
            'timezone' => "{$timezone} ($timezoneOffset)"
        ];

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $result
            )
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /v1/time-duration/list Days
     * @apiDescription  Get report for days
     *
     * @apiVersion      1.0.0
     * @apiName         Days
     * @apiGroup        Project Report
     *
     * @apiPermission   time_duration_list
     */
    /**
     * @deprecated
     * @codeCoverageIgnore
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function days(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            Filter::process(
                $this->getEventUniqueName('validation.report.show'),
                $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.report.show'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $uids = $request->input('uids', []);

        $timezone = $this->timezone;
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $days = ProjectReport::query()
            ->select('user_id', 'date',
                DB::raw("DATE(CONVERT_TZ(date, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(duration) as duration')
            )
            ->whereIn('project_id', Project::getUserRelatedProjectIds($user))
            ->where('date', '>=', $startAt)
            ->where('date', '<', $endAt)
            ->groupBy('user_id', 'date');

        if (!empty($uids)) {
            $days->whereIn('user_id', $uids);
        }

        $days = $days->get();

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $days
            )
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /v1/project-report/projects Projects
     * @apiDescription  Get report for projects
     *
     * @apiVersion      1.0.0
     * @apiName         Projects
     * @apiGroup        Project Report
     *
     * @apiPermission   project_report_projects
     */
    /**
     * @deprecated
     * @codeCoverageIgnore
     * @param  Request  $request
     * @return JsonResponse
     */
    public function projects(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules()
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error_type' => 'validation',
                'message' => 'Validation error',
                'info' => $validator->errors()
            ], 400);
        }

        $uids = $request->input('uids', []);
        // Get projects, where specified users is attached.
        $users_attached_project_ids = Project::whereHas('users', function ($query) use ($uids) {
            $query->whereIn('id', $uids);
        })->pluck('id');

        // Get projects, where specified users have intervals.
        $users_related_project_ids = Project::whereHas('tasks.timeIntervals', function ($query) use ($uids) {
            $query->whereIn('user_id', $uids);
        })->pluck('id');

        $project_ids = collect([$users_attached_project_ids, $users_related_project_ids])->collapse()->unique();

        // Get projects, directly attached to the current user.
        $attached_project_ids = Project::whereHas('users', function ($query) use ($uids) {
            $query->where('id', Auth::user()->id);
        })->pluck('id');

        // Filter projects by directly attached to the current user, if have attached.
        if ($attached_project_ids->count() > 0) {
            $project_ids = $project_ids->intersect($attached_project_ids);
        }

        // Load projects.
        $projects = Project::query()->whereIn('id', $project_ids)->get(['id', 'name']);

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $projects
            )
        );
    }

    /**
     * Returns durations per date for a task.
     *
     * @param           $id
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function task($id, Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            Filter::process(
                $this->getEventUniqueName('validation.report.show'),
                $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process(
                    $this->getEventUniqueName('answer.error.report.show'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $uid = $request->uid;

        $timezone = $this->timezone;
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P'); # Format +00:00

        $startAt = Carbon::parse($request->input('start_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($request->input('end_at'), $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $report = ProjectReport::query()
            ->select(
                DB::raw("DATE(CONVERT_TZ(date, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(duration) as duration')
            )
            ->where('task_id', $id)
            ->where('user_id', $uid)
            ->where('date', '>=', $startAt)
            ->where('date', '<', $endAt)
            ->get(['date', 'duration']);

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $report
            )
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/project-report/screenshots Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         Screenshots
     * @apiGroup        Project Report
     */
    /**
     * @param  Request  $request
     * @deprecated
     * @codeCoverageIgnore
     * @return JsonResponse
     */
    public function screenshots(Request $request): JsonResponse
    {
        $taskID = $request->input('task_id');
        $date = $request->input('date');

        $startDate = Carbon::parse($date);
        $endDate = clone $startDate;
        $endDate = $endDate->addDay();

        $result = TimeInterval::where('task_id', '=', $taskID)
            ->where('start_at', '>=', $startDate->toDateTimeString())
            ->where('start_at', '<', $endDate->toDateTimeString())
            ->with('screenshots')
            ->get()
            ->pluck('screenshots');

        return response()->json($result);
    }
}
