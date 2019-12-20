<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Models\Project;
use App\Models\Property;
use App\Models\TimeInterval;
use Auth;
use Carbon\Carbon;
use DB;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Reports\Entities\ProjectReport;
use Validator;

class ProjectReportController extends ReportController
{
    /**
     * @var
     */
    protected $timezone;

    /**
     * ProjectReportController constructor.
     */
    public function __construct()
    {
        $companyTimezoneProperty = Property::getProperty(Property::COMPANY_CODE, 'TIMEZONE')->first();
        $this->timezone = $companyTimezoneProperty ? $companyTimezoneProperty->getAttribute('value') : 'UTC';

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
     * [report description]
     *
     * @param  Request  $request
     *
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
                    'reason' => $validator->errors()
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

        $pids = array_unique(
            array_merge($pids, Project::getUserRelatedProjectIds(request()->user()))
        );

        $report = DB::table('projects')
            ->select([
                DB::raw('projects.id as project_id'),
                DB::raw('projects.name as project_name'),
                DB::raw('tasks.id as task_id'),
                DB::raw('tasks.task_name as task_name'),
                DB::raw('users.id as user_id'),
                DB::raw('users.full_name as user_name'),
                DB::raw('SUM(TIME_TO_SEC(TIMEDIFF(time_intervals.end_at, time_intervals.start_at))) as task_duration'),
                DB::raw(
                    "DATE_FORMAT(CONVERT_TZ(time_intervals.start_at, '+00:00', '$timezoneOffset'), '%Y-%m-%d') as task_date"
                ),
                DB::raw(
                    "JSON_ARRAYAGG(JSON_OBJECT('id', screenshots.id, 'path', screenshots.path, 'thumbnail_path', screenshots.thumbnail_path, 'created_at', screenshots.created_at)) as screens"
                )
            ])
            ->join('tasks', 'tasks.project_id', '=', 'projects.id')
            ->join('time_intervals', function ($join) use ($startAt, $endAt) {
                $join
                    ->on('time_intervals.task_id', '=', 'tasks.id')
                    ->where('time_intervals.start_at', '>=', $startAt)
                    ->where('time_intervals.end_at', '<', $endAt);
            })
            ->join('users', function ($join) use ($uids) {
                $join
                    ->on('time_intervals.user_id', '=', 'users.id')
                    ->whereIn('users.id', $uids);
            })
            ->join('screenshots', 'screenshots.time_interval_id', '=', 'time_intervals.id')
            ->whereIn('projects.id', $pids)
            ->groupBy(['task_id', 'task_date'])
            ->orderBy('task_date', 'ASC')
            ->orderBy(DB::raw('ANY_VALUE(screenshots.created_at)'), 'ASC');


        $collection = $report->get();

        $resultCollection = [];

        $collection = $collection->groupBy('project_name');

        foreach ($collection as $projectName => $items) {
            foreach ($items as $item) {
                if (!array_key_exists($projectName, $resultCollection)) {
                    $resultCollection[$projectName] = [
                        'id' => $item->project_id,
                        'name' => $item->project_name,
                        'project_time' => 0,
                        'users' => [],
                    ];
                }
                if (!array_key_exists($item->user_id, $resultCollection[$projectName]['users'])) {
                    $resultCollection[$projectName]['users'][$item->user_id] = [
                        'id' => $item->user_id,
                        'full_name' => $item->user_name,
                        'tasks' => [],
                        'tasks_time' => 0,
                    ];
                }
                if (!array_key_exists($item->task_id,
                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'])) {

                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id] = [
                        'task_name' => $item->task_name,
                        'id' => $item->task_id,
                        'duration' => 0,
                        'screenshots' => [],
                    ];
                }

                $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id]['duration'] +=
                    $item->task_duration;

                $screenshotsCollection = collect(json_decode($item->screens, true))
                    ->groupBy(function ($screen) {
                        return Carbon::parse($screen['created_at'])->format('Y-m-d');
                    })
                    ->transform(function ($screen) {
                        return $screen->groupBy(function ($screen) {
                            return Carbon::parse($screen['created_at'])->startOfHour()->format('H:i');
                        });
                    });

                $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id]['screenshots'] =
                    $screenshotsCollection;

            }
        }

        foreach ($resultCollection as &$project) {
            foreach ($project['users'] as &$user) {
                usort($user['tasks'], function ($a, $b) {
                    return $a['duration'] < $b['duration'];
                });

                foreach ($user['tasks'] as $task) {
                    $user['tasks_time'] += $task['duration'];
                }

                $project['project_time'] += $user['tasks_time'];
            }

            usort($project['users'], function ($a, $b) {
                return $a['tasks_time'] < $b['tasks_time'];
            });
        }

        usort($resultCollection, function ($a, $b) {
            return $a['project_time'] < $b['project_time'];
        });

        // TODO: \/ refactor collection processing please \/ <3
        /*
        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;

            if (!isset($projects[$project_id])) {
                $projects[$project_id] = [
                    'id' => $project_id,
                    'name' => $projectReport->project_name,
                    'users' => [],
                    'project_time' => 0,
                ];
            }

            if (!isset($projects[$project_id]['users'][$user_id])) {
                $projects[$project_id]['users'][$user_id] = [
                    'id' => $user_id,
                    'full_name' => $projectReport->user_name,
                    'tasks' => [],
                    'tasks_time' => 0,
                ];
            }

            if (isset($projectReport->task)) {
                $screenshots = $projectReport->task->timeIntervals()
                    ->where('start_at', '>=', $startAt)->where('end_at', '<=', $endAt)->get()
                    ->groupBy(function ($s) {
                        return Carbon::parse($s['start_at'])->startOfDay()->format('Y-m-d');
                    })
                    ->transform(function ($item, $k) {
                        return $item->groupBy(function ($screen) {
                            return Carbon::parse($screen['start_at'])->startOfHour()->format('h:i');
                        });
                    });
            } else {
                $screenshots = [];
            }

            $projects[$project_id]['users'][$user_id]['tasks'][] = [
                'id' => $projectReport->task_id,
                'project_id' => $projectReport->project_id,
                'user_id' => $projectReport->user_id,
                'task_name' => $projectReport->task_name,
                'duration' => (int)$projectReport->duration,
                'screenshots' => $screenshots,
            ];

            $projects[$project_id]['users'][$user_id]['tasks_time'] += $projectReport->duration;
            $projects[$project_id]['project_time'] += $projectReport->duration;
        }*/


        /*foreach ($projects as $project_id => $project) {
            $projects[$project_id]['users'] = array_values($project['users']);
        }

        $projects = array_values($projects);*/
        // TODO: END /\ refactor collection processing please /\ <3


        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.report.show'),
                $resultCollection
            )
        );
    }

    /**
     * [events description]
     *
     * @param  Request  $request  [description]
     *
     * @return JsonResponse [description]
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
     * @param  Request  $request
     *
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
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function screenshots(Request $request)
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
