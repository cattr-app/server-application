<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use Auth;
use Carbon\Carbon;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Validator;
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
     * @apiDefine Relations
     * @apiParam {Object} [task]        `QueryParam` TimeInterval's relation task. All params in <a href="#api-Task-GetTaskList" >@Task</a>
     * @apiParam {Object} [user]        `QueryParam` TimeInterval's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [screenshots] `QueryParam` TimeInterval's relation screenshots. All params in <a href="#api-Screenshot-GetScreenshotList" >@Screenshot</a>
     */

    /**
     * @apiDefine RelationsExample
     * @apiParamExample {json} Request-With-Relations-Example:
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
     * Display a total of time.
     *
     * @api {POST|GET} /api/v1/time/total Total
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        ["=", [1,2,3]],
     *      "project_id":     [">", 1],
     *      "start_at":       "2005-01-01 00:00:00",
     *      "end_at":         "2019-01-01 00:00:00",
     *      "count_mouse":    [">=", 30],
     *      "count_keyboard": ["<=", 200],
     *      "id":             [">", 1]
     *  }
     * @apiUse RelationsExample
     * @apiDescription Get total of Time
     * @apiVersion 0.1.0
     * @apiName GetTimeTotal
     * @apiGroup Time
     *
     * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval's Task ID
     * @apiParam {Integer}   [project_id]     `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval's Task's User ID
     * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
     * @apiParam {String}    [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}   [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {String}   current_datetime Current datetime of server
     * @apiSuccess {Integer}  time             Total time in seconds
     * @apiSuccess {DateTime} start            Datetime of first Time Interval's start_at
     * @apiSuccess {DateTime} end              DateTime of last Time Interval's end_at
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function total(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );
        $time_intervals = $itemsQuery->get();
        $total_time = 0;

        if (collect($time_intervals)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        foreach ($time_intervals as $interval) {
            $total_time += Carbon::parse($interval->end_at)->timestamp - Carbon::parse($interval->start_at)->timestamp;
        }

        $first = collect($time_intervals)->first();
        $last = collect($time_intervals)->last();
        $items = [
            'current_datetime' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'time' => $total_time,
            'start' => Carbon::parse($first->start_at)->format('Y-m-d\TH:i:sP'),
            'end' => Carbon::parse($last->end_at)->format('Y-m-d\TH:i:sP'),
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $items
        ));
    }

    /**
     * Display the project time.
     *
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
     * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval's Task ID
     * @apiParam {Integer}   project_id       `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval's Task's User ID
     * @apiParam {DateTime}  [start_at]                    TimeInterval Start DataTime
     * @apiParam {DateTime}  [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}   [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {DateTime} current_datetime Current datetime of server
     * @apiSuccess {Integer}  time             Total time of project in seconds
     * @apiSuccess {DateTime} start            Datetime of first Time Interval's start_at
     * @apiSuccess {DateTime} end              DateTime of last Time Interval's end_at
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function project(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

        $validator = Validator::make(
            $filters,
            Filter::process($this->getEventUniqueName('validation.item.get'), ['project_id' => 'required'])
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.get'), [
                    'error' => 'validation fail',
                    'reason' => 'project_id is required'
                ]),
                400
            );
        }

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );
        $time_intervals = $itemsQuery->get();
        $total_time = 0;

        if (collect($time_intervals)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        foreach ($time_intervals as $interval) {
            $total_time += Carbon::parse($interval->end_at)->timestamp - Carbon::parse($interval->start_at)->timestamp;
        }

        $first = collect($time_intervals)->first();
        $last = collect($time_intervals)->last();
        $items = [
            'current_datetime' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'time' => $total_time,
            'start' => Carbon::parse($first->start_at)->format('Y-m-d\TH:i:sP'),
            'end' => Carbon::parse($last->end_at)->format('Y-m-d\TH:i:sP'),
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $items
        ));
    }

    /**
     * Display the Tasks and its total time.
     *
     * @api {POST|GET} /api/v1/time/tasks Tasks
     * @apiParamExample {json} Request-Example:
     *  {
     *      "user_id":        1,
     *      "task_id":        [">", 1],
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
     * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval's Task ID
     * @apiParam {Integer}   [project_id]     `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval's Task's User ID
     * @apiParam {DateTime}  [start_at]                    TimeInterval Start DataTime
     * @apiParam {DateTime}  [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}   [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {DateTime} current_datetime Current datetime of server
     * @apiSuccess {Object[]} tasks            Array of objects Task
     * @apiSuccess {Integer}  tasks.id         Tasks's ID
     * @apiSuccess {Integer}  tasks.user_id    Tasks's User ID
     * @apiSuccess {Integer}  tasks.project_id Tasks's Project ID
     * @apiSuccess {Integer}  tasks.time       Tasks's total time in seconds
     * @apiSuccess {DateTime} tasks.start      Datetime of first Tasks's Time Interval's start_at
     * @apiSuccess {DateTime} tasks.end        Datetime of last Tasks's Time Interval's end_at
     * @apiSuccess {Total[]}  total            Array of total tasks time
     * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
     * @apiSuccess {DateTime} total.start      Datetime of first Time Interval's start_at
     * @apiSuccess {DateTime} total.end        DateTime of last Time Interval's end_at
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function tasks(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

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
                'time'  => $total_time,
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
     * @apiParam {Integer}  task_id                       TimeInterval's Task ID
     * @apiParam {Integer}  [project_id]     `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer}  [user_id]        `QueryParam` TimeInterval's Task's User ID
     * @apiParam {DateTime} [start_at]                    TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at]                      TimeInterval End DataTime
     * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}  [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {DateTime} current_datetime Current datetime of server
     * @apiSuccess {Object[]} tasks            Array of objects Task
     * @apiSuccess {Integer}  tasks.id         Tasks's ID
     * @apiSuccess {Integer}  tasks.user_id    Tasks's User ID
     * @apiSuccess {Integer}  tasks.project_id Tasks's Project ID
     * @apiSuccess {Integer}  tasks.time       Tasks's total time in seconds
     * @apiSuccess {DateTime} tasks.start      Datetime of first Tasks's Time Interval's start_at
     * @apiSuccess {DateTime} tasks.end        Datetime of last Tasks's Time Interval's end_at
     * @apiSuccess {Total[]}  total            Array of total tasks time
     * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
     * @apiSuccess {DateTime} total.start      Datetime of first Time Interval's start_at
     * @apiSuccess {DateTime} total.end        DateTime of last Time Interval's end_at
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function task(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        is_int($request->get('task_id')) ? $filters['task_id'] = $request->get('task_id') : False;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

        $validator = Validator::make(
            $filters,
            Filter::process($this->getEventUniqueName('validation.item.get'), ['task_id' => 'required'])
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.get'), [
                    'error' => 'validation fail',
                    'reason' => 'task_id is required'
                ]),
                400
            );
        }

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
                'time'  => $total_time,
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
     * @apiParam {Integer}  task_id                       TimeInterval's Task ID
     * @apiParam {Integer}  user_id                       TimeInterval's Task's User ID
     * @apiParam {DateTime} [start_at]       `QueryParam` TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at]         `QueryParam` TimeInterval End DataTime
     * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
     * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
     * @apiParam {Integer}  [id]             `QueryParam` TimeInterval ID
     * @apiUse Relations
     *
     * @apiSuccess {DateTime} current_datetime Current datetime of server
     * @apiSuccess {Integer}  id               Task's ID
     * @apiSuccess {Integer}  user_id          Task's User's ID
     * @apiSuccess {Integer}  time             Total time of task in seconds
     * @apiSuccess {DateTime} start            Datetime of first Task's Time Interval's start_at
     * @apiSuccess {DateTime} end              DateTime of last Task's Time Interval's end_at
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function taskUser(Request $request): JsonResponse
    {
        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['user_id'] = $request->get('user_id') : False;
        is_int($request->get('task_id')) ? $filters['task_id'] = $request->get('task_id') : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $validator = Validator::make(
            $filters,
            Filter::process($this->getEventUniqueName('validation.item.get'),
                [
                    'task_id' => 'required',
                    'user_id' => 'required'
                ]
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.get'), [
                    'error' => 'validation fail',
                    'reason' => 'task_id and user_id is required'
                ]),
                400
            );
        }

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );
        $time_intervals = $itemsQuery->get();

        if (collect($time_intervals)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        $time = 0;
        $first = $time_intervals->first();
        $last = $time_intervals->last();

        foreach ($time_intervals as $interval) {
            $time += Carbon::parse($interval->end_at)->timestamp - Carbon::parse($interval->start_at)->timestamp;
        }

        $response = [
            'current_datetime' => Carbon::now()->format('Y-m-d\TH:i:sP'),
            'id' => $first->task_id,
            'user_id' => $first->user_id,
            'time' => $time,
            'start' => Carbon::parse($first->start_at)->format('Y-m-d\TH:i:sP'),
            'end' => Carbon::parse($last->end_at)->format('Y-m-d\TH:i:sP')
        ];

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $response
        ));
    }

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'time', 'full_access');
        $relations_access = Role::can(Auth::user(), 'users', 'relations');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');

        if ($full_access) {
            return $query;
        }

        $user_time_interval_id = collect(Auth::user()->timeIntervals)->flatMap(function($val) {
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

        if ($relations_access) {
            $attached_users_time_intervals_id = collect(Auth::user()->attached_users)->flatMap(function($val) {
                return collect($val->timeIntervals)->pluck('id');
            });
            $time_intervals_id = collect([$time_intervals_id, $user_time_interval_id, $attached_users_time_intervals_id])->collapse()->unique();
        } else {
            $time_intervals_id = collect([$time_intervals_id, $user_time_interval_id])->collapse()->unique();
        }
        $query->whereIn('time_intervals.id', $time_intervals_id);

        return $query;
    }
}
