<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use App\Models\TimeInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class TimeController
 *
 * @package App\Http\Controllers\Api\v1
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
     * @apiDefine Relations
     * @apiParam {Object} [task]        `QueryParam` TimeInterval's relation task. All params in <a href="#api-Task-GetTaskList" >@Task</a>
     * @apiParam {Object} [user]        `QueryParam` TimeInterval's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [screenshots] `QueryParam` TimeInterval's relation screenshots. All params in <a href="#api-Screenshot-GetScreenshotList" >@Screenshot</a>
     */

    /**
     * @apiDefine RelationsExample
     * @apiParamExample {json} Request With Relations Example
     *  {
     *      "with":           "task,user,screenshots"
     *      "task.id":        [">", 1],
     *      "task.active":    1,
     *      "user.id":        [">", 1],
     *      "user.full_name": ["like", "%lorem%"],
     *      "screenshots.id": [">", 1]
     *  }
     */

    /**
     * @api             {get, post} /v1/time/total Total
     * @apiDescription  Get total of Time
     *
     * @apiVersion      1.0.0
     * @apiName         GetTimeTotal
     * @apiGroup        Time
     *
     * @apiParam {String}   start_at  Start DataTime
     * @apiParam {String}   end_at    End DataTime
     * @apiParam {Integer}  user_id   User ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "user_id": 1,
     *      "start_at": "2005-01-01 00:00:00",
     *      "end_at": "2019-01-01 00:00:00"
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
     */
    /**
     * Display a total of time
     * @param Request $request
     * @return JsonResponse
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
     * Display the project time.
     * @api {POST|GET} /api/v1/time/project Project
     * @apiParamExample {json} Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        ["=", [1,2,3]],
     *      "project_id":     ["<", 2],
     *      "start_at":       "2005-01-01 00:00:00",
     *      "end_at":         "2019-01-01 00:00:00",
     *      "count_mouse":    [">=", 30],
     *      "count_keyboard": ["<=", 200],
     *      "id":             [">", 1]
     *  }
     * @apiUse RelationsExample
     * @apiDescription Get time of project
     * @apiVersion 0.1.0
     * @apiName GetTimeByProject
     * @apiGroup Time
     *
     * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval Task id
     * @apiParam {Integer}   project_id       `QueryParam` TimeInterval Task Project id
     * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval Task User id
     * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
     * @apiParam {String}    [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}   [id]             `QueryParam` TimeInterval id
     * @apiUse Relations
     *
     * @apiSuccess {String}   current_datetime Current datetime of server
     * @apiSuccess {Integer}  time             Total time of project in seconds
     * @apiSuccess {String}   start            Datetime of first Time Interval's start_at
     * @apiSuccess {String}   end              DateTime of last Time Interval's end_at
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     */


    /**
     * Display the Tasks and theirs total time.
     *
     * @param Request $request
     * @return JsonResponse
     * @api {POST|GET} /api/v1/time/tasks Tasks
     * @apiParamExample {json} Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        1 OR [1, 2, n] (multiple choice can be only achieved with POST),
     *      "project_id":     2,
     *      "start_at":       "2005-01-01 00:00:00",
     *      "end_at":         "2019-01-01 00:00:00",
     *      "count_mouse":    [">=", 30],
     *      "count_keyboard": ["<=", 200],
     *      "id":             [">", 1]
     *  }
     * @apiUse RelationsExample
     * @apiDescription Get tasks and its total time
     * @apiVersion 0.1.0
     * @apiName GetTimeByTasks
     * @apiGroup Time
     *
     * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval Task id
     * @apiParam {Integer}   [project_id]     `QueryParam` TimeInterval Task Project id
     * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval Task User id
     * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
     * @apiParam {String}    [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}   [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {String}   current_datetime Current datetime of server
     * @apiSuccess {Object[]} tasks            Array of objects Task
     * @apiSuccess {Integer}  tasks.id         Tasks id
     * @apiSuccess {Integer}  tasks.user_id    Tasks User id
     * @apiSuccess {Integer}  tasks.project_id Tasks Project id
     * @apiSuccess {Integer}  tasks.time       Tasks total time in seconds
     * @apiSuccess {String}   tasks.start      Datetime of first Tasks Time Interval start_at
     * @apiSuccess {String}   tasks.end        Datetime of last Tasks Time Interval end_at
     * @apiSuccess {Object[]} total            Array of total tasks time
     * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
     * @apiSuccess {String}   total.start      Datetime of first Time Interval start_at
     * @apiSuccess {String}   total.end        DateTime of last Time Interval end_at
     *
     */
    public function tasks(Request $request): JsonResponse
    {
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

        $time_intervals = $itemsQuery->get()->groupBy('task_id')->map(function ($item, $key) {
            return collect($item)->groupBy('user_id');
        });

        if (collect($time_intervals)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        $taskList = $itemsQuery->with('task')->get()->map(function ($item, $key) {
            return collect($item)->only('task');
        })->flatten(1)->unique()->values()->all();

        $total_time = 0;
        $tasks = [];

        foreach ($time_intervals as $task => $intervals_task) {
            foreach ($intervals_task as $user => $intervals_user) {
                $time = 0;

                foreach ($intervals_user as $key => $interval) {
                    $time += Carbon::parse($interval->end_at)->timestamp - Carbon::parse($interval->start_at)->timestamp;
                }

                $first = $intervals_user->first();
                $last = $intervals_user->last();
                $tasks[] = [
                    'id' => $task,
                    'user_id' => $user,
                    'project_id' => collect($taskList)->filter(function ($value) use ($task) {
                        return $value['id'] === $task;
                    })->first()['project_id'],
                    'time' => $time,
                    'start' => Carbon::parse($first->start_at)->format('Y-m-d\TH:i:sP'),
                    'end' => Carbon::parse($last->end_at)->format('Y-m-d\TH:i:sP')
                ];
                $total_time += $time;
            }
        }

        $first = $itemsQuery->get()->first();
        $last = $itemsQuery->get()->last();
        $response = [
            'current_datetime' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'tasks' => $tasks,
            'total' => [
                'time' => $total_time,
                'start' => Carbon::parse($first->start_at)->format('Y-m-d\TH:i:sP'),
                'end' => Carbon::parse($last->end_at)->format('Y-m-d\TH:i:sP'),
            ]
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $response
        ));
    }

    /**
     * Display the Task and its total time.
     *
     * @api {POST|GET} /api/v1/time/task Task
     * @apiParamExample {json} Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        1,
     *      "project_id":     2,
     *      "start_at":       "2005-01-01 00:00:00",
     *      "end_at":         "2019-01-01 00:00:00",
     *      "count_mouse":    [">=", 30],
     *      "count_keyboard": ["<=", 200],
     *      "id":             [">", 1]
     *  }
     * @apiUse RelationsExample
     * @apiDescription Get task and its total time
     * @apiVersion 0.1.0
     * @apiName GetTimeBySingleTask
     * @apiGroup Time
     *
     * @apiParam {Integer}  task_id                       TimeInterval Task id
     * @apiParam {Integer}  [project_id]     `QueryParam` TimeInterval Task Project id
     * @apiParam {Integer}  [user_id]        `QueryParam` TimeInterval Task User id
     * @apiParam {String}   [start_at]                    TimeInterval Start DataTime
     * @apiParam {String}   [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}  [id]             `QueryParam` TimeInterval id
     * @apiUse Relations
     *
     * @apiSuccess {String}   current_datetime Current datetime of server
     * @apiSuccess {Object[]} tasks            Tasks
     * @apiSuccess {Integer}  tasks.id         Task id
     * @apiSuccess {Integer}  tasks.user_id    Task User id
     * @apiSuccess {Integer}  tasks.project_id Task Project id
     * @apiSuccess {Integer}  tasks.time       Task total time in seconds
     * @apiSuccess {String}   tasks.start      Datetime of first Tasks's Time Interval's start_at
     * @apiSuccess {String}   tasks.end        Datetime of last Tasks's Time Interval's end_at
     * @apiSuccess {Object[]} total            Total tasks time
     * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
     * @apiSuccess {String}   total.start      Datetime of first Time Interval's start_at
     * @apiSuccess {String}   total.end        DateTime of last Time Interval's end_at
     *
     * @apiError (Error 400) {String} error    Name of error
     * @apiError (Error 400) {String} reason   Reason of error
     *
     */

    /**
     * Display time of user's single task.
     *
     * @api {POST|GET} /api/v1/time/task-user TaskUser
     * @apiParamExample {json} Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        1,
     *      "start_at":       [">=", "2005-01-01 00:00:00"],
     *      "end_at":         ["<=", "2019-01-01 00:00:00"],
     *      "count_mouse":    [">=", 30],
     *      "count_keyboard": ["<=", 200],
     *      "id":             [">", 1]
     *  }
     * @apiUse RelationsExample
     * @apiDescription Get time of user's single task
     * @apiVersion 0.1.0
     * @apiName GetTimeBySingleTaskAndUser
     * @apiGroup Time
     *
     * @apiParam {Integer}  task_id                       TimeInterval Task ID
     * @apiParam {Integer}  user_id                       TimeInterval Task User ID
     * @apiParam {String}   [start_at]       `QueryParam` TimeInterval Start DataTime
     * @apiParam {String}   [end_at]         `QueryParam` TimeInterval End DataTime
     * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}  [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {DateTime} current_datetime Current datetime of server
     * @apiSuccess {Integer}  id               Task id
     * @apiSuccess {Integer}  user_id          Task's User id
     * @apiSuccess {Integer}  time             Total time of task in seconds
     * @apiSuccess {String}   start            Datetime of first Task's Time Interval's start_at
     * @apiSuccess {String}   end              DateTime of last Task's Time Interval's end_at
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
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
