<?php

namespace App\Http\Controllers\Api\v1;

use App\EventFilter\Facades\Filter;
use App\Models\Role;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class TimeController
*/
class TimeController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return TimeInterval::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'time';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'total' => 'time.total',
            'project' => 'time.project',
            'tasks' => 'time.tasks',
            'task' => 'time.task',
            'taskUser' => 'time.task-user',
        ];
    }

    /**
     * @api             {get,post} /v1/time/total Total
     * @apiDescription  Get total of Time
     *
     * @apiVersion      1.0.0
     * @apiName         Total
     * @apiGroup        Time
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_total
     * @apiPermission   time_full_access
     *
     * @apiParam {String}   start_at  Start DataTime
     * @apiParam {String}   end_at    End DataTime
     * @apiParam {Integer}  user_id   User ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_id": 1,
     *    "start_at": "2005-01-01 00:00:00",
     *    "end_at": "2019-01-01 00:00:00"
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when TRUE
     * @apiSuccess {Boolean}  time     Total time in seconds
     * @apiSuccess {String}   start    Datetime of first Time Interval start_at
     * @apiSuccess {String}   end      DateTime of last Time Interval end_at
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "time": 338230,
     *    "start": "2020-01-23T19:42:27+00:00",
     *    "end": "2020-04-30T21:58:31+00:00"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */
    /**
     * Display a total of time
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function total(Request $request): JsonResponse
    {
        $validationRules = [
            'start_at' => 'required|date',
            'end_at' => 'required|date',
            'user_id' => 'required|integer|exists:users,id'
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.time.total'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }
        $filters = [
            'start_at' => ['>=', $request->get('start_at')],
            'end_at' => ['<=', $request->get('end_at')],
            'user_id' => ['=', $request->get('user_id')]
        ];

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter($this->getQuery(), $filters)
        );

        $timeIntervals = $itemsQuery->get();

        $totalTime = $timeIntervals->sum(static function ($interval) {
            return Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
        });

        $responseData = [
            'success' => true,
            'time' => $totalTime,
            'start' => $timeIntervals->min('start_at'),
            'end' => $timeIntervals->max('end_at')
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $responseData
        ));
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get, post} /v1/time/project Project
     * @apiDescription  Get time of project
     *
     * @apiVersion      1.0.0
     * @apiName         Project
     * @apiGroup        Time
     *
     * @apiPermission   time_project
     * @apiPermission   time_full_access
     */

    /**
     * @api             {get,post} /v1/time/tasks Tasks
     * @apiDescription  Get tasks and its total time
     *
     * @apiVersion      1.0.0
     * @apiName         Tasks
     * @apiGroup        Time
     *
     * @apiUse          TimeIntervalParams
     *
     * @apiParamExample {json} Request Example:
     *  {
     *    "user_id": 1,
     *    "task_id": 1,
     *    "project_id": 2,
     *    "start_at": "2005-01-01 00:00:00",
     *    "end_at": "2019-01-01 00:00:00",
     *    "count_mouse": [">=", 30],
     *    "count_keyboard": ["<=", 200],
     *    "id": [">", 1]
     *  }
     *
     * @apiSuccess {String}    current_datetime  Current datetime of server
     * @apiSuccess {Object[]}  tasks             Array of objects Task
     * @apiSuccess {Integer}   tasks.id          Tasks id
     * @apiSuccess {Integer}   tasks.user_id     Tasks User id
     * @apiSuccess {Integer}   tasks.project_id  Tasks Project id
     * @apiSuccess {Integer}   tasks.time        Tasks total time in seconds
     * @apiSuccess {String}    tasks.start       Datetime of first Tasks Time Interval start_at
     * @apiSuccess {String}    tasks.end         Datetime of last Tasks Time Interval end_at
     * @apiSuccess {Object[]}  total             Array of total tasks time
     * @apiSuccess {Integer}   total.time        Total time of tasks in seconds
     * @apiSuccess {String}    total.start       Datetime of first Time Interval start_at
     * @apiSuccess {String}    total.end         DateTime of last Time Interval end_at
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "current_datetime": "2020-01-28T10:57:40+00:00",
     *     "tasks": [
     *       {
     *         "id": 1,
     *         "user_id": 1,
     *         "project_id": 1,
     *         "time": 1490,
     *         "start": "2020-01-23T19:42:27+00:00",
     *         "end": "2020-01-23T20:07:21+00:00"
     *       },
     *     ],
     *     "total": {
     *     "time": 971480,
     *     "start": "2020-01-23T19:42:27+00:00",
     *     "end": "2020-11-01T08:28:06+00:00"
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ValidationError
     */
    /**
     * Display the Tasks and theirs total time
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function tasks(Request $request): JsonResponse
    {
        $validationRules = [
            'start_at' => 'date',
            'end_at' => 'date',
            'project_id' => 'exists:projects,id',
            'task_id' => 'exists:tasks,id'
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.time.total'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }

        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string)$request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string)$request->get('end_at')] : False;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;
        $request->get('task_id') ? $filters['task_id'] = ['in', $request->get('task_id')] : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        $totalTime = 0;

        $tasks = $itemsQuery
            ->with('task')
            ->get()
            ->groupBy(['task_id', 'user_id'])
            ->map(function($taskIntervals, $taskId) use (&$totalTime) {
                $task = [];

                foreach ($taskIntervals as $userId => $userIntervals) {
                    $taskTime = 0;

                    foreach ($userIntervals as $interval) {
                        $taskTime += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
                    }

                    $firstUserInterval = $userIntervals->first();
                    $lastUserInterval = $userIntervals->last();

                    $task = [
                        'id' => $taskId,
                        'user_id' => $userId,
                        'project_id' => $userIntervals[0]['task']['project_id'],
                        'time' => $taskTime,
                        'start' => Carbon::parse($firstUserInterval->start_at)->toISOString(),
                        'end' => Carbon::parse($lastUserInterval->end_at)->toISOString()
                    ];

                    $totalTime += $taskTime;
                }

                return $task;
            })
            ->values();

        $first = $itemsQuery->get()->first();
        $last = $itemsQuery->get()->last();

        $response = [
            'success' => true,
            'tasks' => $tasks,
            'total' => [
                'time' => $totalTime,
                'start' => $first ? Carbon::parse($first->start_at)->toISOString() : null,
                'end' => $last ? Carbon::parse($last->end_at)->toISOString() : null,
            ]
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $response
        ));
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get, post} /v1/time/task Task
     * @apiDescription  Get task and its total time
     *
     * @apiVersion      1.0.0
     * @apiName         Task
     * @apiGroup        Time
     *
     * @apiPermission   time_task
     * @apiPermission   time_full_access
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get,post} /v1/time/task-user TaskUser
     * @apiDescription  Get time of user's single task
     *
     * @apiVersion      1.0.0
     * @apiName         TaskAndUser
     * @apiGroup        Time
     *
     * @apiPermission   time_task_user
     * @apiPermission   time_full_access
     */

    /**
     * @param bool $withRelations
     *
     * @param bool $withSoftDeleted
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can(Auth::user(), 'time', 'full_access');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');

        if ($full_access) {
            return $query;
        }

        $user_time_interval_id = collect(Auth::user()->timeIntervals)->flatMap(function ($val) {
            return collect($val->id);
        });
        $time_intervals_id = collect([]);

        if ($project_relations_access) {
            $attached_time_interval_id_to_project = collect(Auth::user()->projects)->flatMap(function ($project) {
                return collect($project->tasks)->flatMap(function ($task) {
                    return collect($task->timeIntervals)->pluck('id');
                });
            });
            $time_intervals_id = collect([$attached_time_interval_id_to_project])->collapse();
        }

        $time_intervals_id = collect([$time_intervals_id, $user_time_interval_id])->collapse()->unique();
        $query->whereIn('time_intervals.id', $time_intervals_id);

        return $query;
    }
}
