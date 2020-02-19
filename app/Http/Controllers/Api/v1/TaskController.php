<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use Exception;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Class TaskController
 */
class TaskController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Task::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id' => 'exists:projects,id|required',
            'task_name' => 'required',
            'active' => 'required',
            'user_id' => 'exists:users,id|required',
            'priority_id' => 'exists:priorities,id|required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'task';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'tasks.list',
            'count' => 'tasks.list',
            'dashboard' => 'tasks.dashboard',
            'create' => 'tasks.create',
            'edit' => 'tasks.edit',
            'show' => 'tasks.show',
            'destroy' => 'tasks.remove',
            'activity' => 'tasks.activity',
        ];
    }

    /**
     * @api             {post} /v1/tasks/list List
     * @apiDescription  Get list of Tasks
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_list
     * @apiPermission   tasks_full_access
     *
     * @apiUse          TaskParams
     * @apiUse          TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 2,
     *      "project_id": 1,
     *      "task_name": "Delectus.",
     *      "description": "Et qui sed qui vero quis. Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *      "active": 1,
     *      "user_id": 1,
     *      "assigned_by": 1,
     *      "url": null,
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "priority_id": 2,
     *      "important": 0
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {post} /v1/tasks/create Create
     * @apiDescription  Create Task
     *
     * @apiVersion      1.0.0
     * @apiName         Create
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_create
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  project_id   Project
     * @apiParam {String}   task_name    Name
     * @apiParam {String}   description  Description
     * @apiParam {String}   url          Url
     * @apiParam {Integer}  active       Active/Inactive Task. Available value: {0,1}
     * @apiParam {Integer}  user_id      User
     * @apiParam {Integer}  assigned_by  User who assigned task
     * @apiParam {Integer}  priority_id  Priority ID
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "project_id":"163",
     *    "task_name":"retr",
     *    "description":"fdgfd",
     *    "active":1,
     *    "user_id":"3",
     *    "assigned_by":"1",
     *    "url":"URL",
     *    "priority_id": 1
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      Task
     *
     * @apiUse TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "id": 2,
     *      "project_id": 1,
     *      "task_name": "Delectus.",
     *      "description": "Et qui sed qui vero quis. Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *      "active": 1,
     *      "user_id": 1,
     *      "assigned_by": 1,
     *      "url": null,
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "priority_id": 2,
     *      "important": 0
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {post} /v1/tasks/show Show
     * @apiDescription  Show Task
     *
     * @apiVersion      1.0.0
     * @apiName         Show
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_show
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  id  ID
     *
     * @apiUse          TaskParams
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "project_id": ["=", [1,2,3]],
     *    "active": 1,
     *    "user_id": ["=", [1,2,3]],
     *    "assigned_by": ["=", [1,2,3]],
     *    "task_name": ["like", "%lorem%"],
     *    "description": ["like", "%lorem%"],
     *    "url": ["like", "%lorem%"],
     *    "created_at": [">", "2019-01-01 00:00:00"],
     *    "updated_at": ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse          TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 2,
     *    "project_id": 1,
     *    "task_name": "Delectus.",
     *    "description": "Et qui sed qui vero quis. Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *    "active": 1,
     *    "user_id": 1,
     *    "assigned_by": 1,
     *    "url": null,
     *    "created_at": "2020-01-23T09:42:26+00:00",
     *    "updated_at": "2020-01-23T09:42:26+00:00",
     *    "deleted_at": null,
     *    "priority_id": 2,
     *    "important": 0
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */

    /**
     * @api             {post} /v1/tasks/edit Edit
     * @apiDescription  Edit Task
     *
     * @apiVersion      1.0.0
     * @apiName         Edit
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_edit
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  id           ID
     * @apiParam {Integer}  project_id   Project
     * @apiParam {Integer}  active       Is Task active. Available value: {0,1}
     * @apiParam {Integer}  user_id      Task User
     * @apiParam {Integer}  priority_id  Priority ID
     *
     * @apiUse         TaskParams
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "project_id": 2,
     *    "active": 1,
     *    "user_id": 3,
     *    "assigned_by": 2,
     *    "task_name": "lorem",
     *    "description": "test",
     *    "url": "url",
     *    "priority_id": 1
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      Task
     *
     * @apiUse         TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "id": 2,
     *      "project_id": 1,
     *      "task_name": "Delectus.",
     *      "description": "Et qui sed qui vero quis. Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *      "active": 1,
     *      "user_id": 1,
     *      "assigned_by": 1,
     *      "url": null,
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "priority_id": 2,
     *      "important": 0
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */

    /**
     * @api             {post} /v1/tasks/remove Destroy
     * @apiDescription  Destroy Task
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_remove
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  id  ID of the target task
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @api             {get,post} /v1/tasks/count Count
     * @apiDescription  Count Tasks
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_count
     * @apiPermission   tasks_full_access
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of tasks that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/tasks/dashboard Dashboard
     * @apiDescription  Display task for dashboard
     *
     * @apiVersion      1.0.0
     * @apiName         Dashboard
     * @apiGroup        Task
     *
     * @apiPermission   tasks_dashboard
     * @apiPermission   tasks_full_access
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @deprecated
     * @codeCoverageIgnore
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = Auth::user();
        $timezone = $user->timezone;
        if (!$timezone) {
            $timezone = 'UTC';
        }

        $uid = $user->id;

        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['timeIntervals.user_id'] = $request->get('user_id') : false;
        $compareDate = Carbon::today($timezone)->setTimezone('UTC')->toIso8601String();
        $filters['timeIntervals.start_at'] = ['>=', [$compareDate]];
        unset($filters['user_id']);

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                $filters ?: []
            )
        );

        $items = $itemsQuery->with([
            'timeIntervals' => function ($q) use ($compareDate, $uid) {
                /** @var Builder $q */
                $q->where('start_at', '>=', $compareDate);
                $q->where('user_id', $uid);
            }
        ])->get()->toArray();

        if (collect($items)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        foreach ($items as $key => $task) {
            $totalTime = 0;

            foreach ($task['time_intervals'] as $timeInterval) {
                $totalTime += abs(Carbon::parse($timeInterval['end_at'])->timestamp - Carbon::parse($timeInterval['start_at'])->timestamp);
            }
            $items[$key]['total_time'] = gmdate("H:i:s", $totalTime);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items)
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/tasks/activity Activity
     * @apiDescription  Display tasks activity
     *
     * @apiVersion      1.0.0
     * @apiName         Activity
     * @apiGroup        Task
     *
     * @apiPermission   tasks_dashboard
     * @apiPermission   tasks_full_access
     */
    /**
     * Returns users activity info for task.
     * @param  Request  $request
     *
     * @return JsonResponse
     * @deprecated
     * @codeCoverageIgnore
     */
    public function activity(Request $request): JsonResponse
    {
        $itemId = is_int($request->get('id')) ? $request->get('id') : false;

        if (!$itemId) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'success'=> false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'Invalid id'
                ]),
                400
            );
        }

        $user = Auth::user();
        $userProjectIds = Project::getUserRelatedProjectIds($user);
        $projectId = Task::find($itemId)->project_id;
        if (!in_array($projectId, $userProjectIds)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'success' => false,
                    'error_type' => 'authorization.forbidden',
                    'message' => 'User has no access to this task'
                ]),
                403
            );
        }

        $timezone = $user->timezone ?: 'UTC';
        $timezoneOffset = (new Carbon())->setTimezone($timezone)->format('P');

        $activity = DB::table('project_report')
            ->select(
                'user_id',
                'user_name',
                'date',
                DB::raw("DATE(CONVERT_TZ(date, '+00:00', '{$timezoneOffset}')) as date"),
                DB::raw('SUM(duration) as duration')
            )
            ->where([
                ['task_id', '=', $itemId],
            ])
            ->groupBy(
                'user_id',
                'user_name',
                'date',
                'duration'
            )
            ->orderBy('date')
            ->get();

        $group_by = $request->get('group_by', 'date');
        if (in_array($group_by, [
            'user_id',
            'user_name',
            'date',
        ])) {
            $activity = $activity->groupBy($group_by);
        }

        return response()->json($activity);
    }

    public function show(Request $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('answer.success.item.show'), function (Task $task) {
            $totalTracked = 0;

            $workers = DB::table('time_intervals AS i')
                ->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
                ->join('users AS u', 'i.user_id', '=', 'u.id')
                ->select('i.user_id', 'u.full_name', 'i.task_id', 'i.start_at', 'i.end_at',
                    DB::raw('SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration')
                )
                ->whereNull('i.deleted_at')
                ->where('task_id', $task->id)
                ->groupBy('i.user_id')
                ->get();

            foreach ($workers as $worker) {
                $totalTracked += $worker->duration;
            }

            $task->workers = $workers;
            $task->total_spent_time = $totalTracked;
            return $task;
        });
        return parent::show($request);
    }

    /**
     * @param bool $withRelations
     *
     * @param bool $withSoftDeleted
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $user = Auth::user();
        $user_id = $user->id;
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can($user, 'tasks', 'full_access');
        $action_method = Route::current()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $rules = $this->getControllerRules();
        $rule = $rules[$action_method] ?? null;
        if (isset($rule)) {
            [$object, $action] = explode('.', $rule);
            // Check user default role
            if (Role::can($user, $object, $action)) {
                return $query;
            }

            $query->where(function (Builder $query) use ($user_id, $object, $action) {
                // Filter by project roles of the user
                $query->whereHas('project.usersRelation', static function (Builder $query) use ($user_id, $object, $action) {
                    $query->where('user_id', $user_id)->whereHas('role', static function (Builder $query) use ($object, $action) {
                        $query->whereHas('rules', static function (Builder $query) use ($object, $action) {
                            $query->where([
                                'object' => $object,
                                'action' => $action,
                                'allow'  => true,
                            ])->select('id');
                        })->select('id');
                    })->select('id');
                });

                // For read-only access include tasks where the user is assigned or has tracked intervals
                $query->when($action !== 'edit' && $action !== 'remove', static function (Builder $query) use ($user_id) {
                    $query->orWhere('user_id', $user_id);

                    $query->orWhereHas('timeIntervals', static function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->select('user_id');
                    });
                });
            });
        }

        return $query;
    }

    protected function applyQueryFilter(Builder $query, array $filter = []): Builder
    {
        if (isset($filter['order_by'])) {
            $order_by = $filter['order_by'];
            [$column, $dir] = \is_array($order_by) ? $order_by : [$order_by, 'asc'];
            if ($column === 'projects.name') {
                // Because Laravel haven't built-in for order by a field in a related table.
                $query
                    ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
                    ->orderBy('projects.name', $dir)
                    ->select('tasks.*');

                unset($filter['order_by']);
            }
        }

        return parent::applyQueryFilter($query, $filter);
    }
}
