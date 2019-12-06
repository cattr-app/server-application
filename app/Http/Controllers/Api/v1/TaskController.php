<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\ProjectsUsers;
use App\Models\Role;
use App\Models\Task;
use Auth;
use Carbon\Carbon;
use DB;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Route;

/**
 * Class TaskController
 *
 * @package App\Http\Controllers\Api\v1
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
     * @apiDefine TaskRelations
     *
     * @apiParam {String} [with]                       For add relation model in response
     * @apiParam {Object} [timeIntervals] `QueryParam` Task's relation Time Intervals. All params in <a href="#api-Time_Interval-GetTimeIntervalList" >@Time_Intervals</a>
     * @apiParam {Object} [user]          `QueryParam` Task's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [assigned]      `QueryParam` Task's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [project]       `QueryParam` Task's relation user. All params in <a href="#api-Project-GetProjectList" >@Project</a>
     */

    /**
     * @apiDefine TaskRelationsExample
     * @apiParamExample {json} Request With Relations Example
     *  {
     *      "with":                "project,user,timeIntervals,assigned"
     *      "user.id":             [">", 1],
     *      "project.task.active": 1,
     *      "assigned.full_name":  ["like", "%lorem%"]
     *  }
     */

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @api            {post} /api/v1/tasks/list List
     * @apiDescription Get list of Tasks
     * @apiVersion     0.1.0
     * @apiName        GetTaskList
     * @apiGroup       Task
     *
     * @apiParam {Integer}  [id]          `QueryParam` Task ID
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Integer}  [active]                   Is Task active. Available value: {0,1}
     * @apiParam {Integer}  [user_id]     `QueryParam` Task User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse         TaskRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          [">", 1]
     *      "project_id":  ["=", [1,2,3]],
     *      "active":      1,
     *      "user_id":     ["=", [1,2,3]],
     *      "assigned_by": ["=", [1,2,3]],
     *      "task_name":   ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "url":         ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse         TaskRelationsExample
     *
     * @apiSuccess {Object[]} TaskList                     Tasks
     * @apiSuccess {Object}   TaskList.Task                Task
     * @apiSuccess {Integer}  TaskList.Task.id             Task id
     * @apiSuccess {Integer}  TaskList.Task.project_id     Task Project id
     * @apiSuccess {Integer}  TaskList.Task.user_id        Task User id
     * @apiSuccess {Integer}  TaskList.Task.active         Task is active
     * @apiSuccess {String}   TaskList.Task.task_name      Task name
     * @apiSuccess {String}   TaskList.Task.description    Task description
     * @apiSuccess {String}   TaskList.Task.url            Task url
     * @apiSuccess {String}   TaskList.Task.created_at     Task date time of create
     * @apiSuccess {String}   TaskList.Task.updated_at     Task date time of update
     * @apiSuccess {String}   TaskList.Task.deleted_at     Task date time of delete
     * @apiSuccess {Object[]} TaskList.Task.time_intervals Task Time intervals
     * @apiSuccess {Object[]} TaskList.Task.user           Task User object
     * @apiSuccess {Object[]} TaskList.Task.assigned       Task assigned User object
     * @apiSuccess {Object[]} TaskList.Task.project        Task Project object
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/tasks/create Create
     * @apiDescription Create Task
     * @apiVersion     0.1.0
     * @apiName        CreateTask
     * @apiGroup       Task
     *
     * @apiParam {Integer} [project_id]  Task Project
     * @apiParam {String}  [task_name]   Task Name
     * @apiParam {String}  [description] Task Description
     * @apiParam {String}  url           Task Url
     * @apiParam {Integer} [active]      Active/Inactive Task. Available value: {0,1}
     * @apiParam {Integer} [user_id]     Task User
     * @apiParam {Integer} [assigned_by] User who assigned task
     * @apiParam {Integer} [priority_id] Task Priority ID
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "project_id":"163",
     *      "task_name":"retr",
     *      "description":"fdgfd",
     *      "active":1,
     *      "user_id":"3",
     *      "assigned_by":"1",
     *      "url":"URL",
     *      "priority_id": 1
     *  }
     *
     * @apiSuccess {Object}   res                Task object
     * @apiSuccess {Integer}  res.id             Task ID
     * @apiSuccess {Integer}  res.project_id     Task Project ID
     * @apiSuccess {Integer}  res.user_id        Task User ID
     * @apiSuccess {Integer}  res.active         Task active status
     * @apiSuccess {String}   res.task_name      Task name
     * @apiSuccess {String}   res.description    Task description
     * @apiSuccess {String}   res.url            Task url
     * @apiSuccess {String}   res.created_at     Task date time of create
     * @apiSuccess {String}   res.updated_at     Task date time of update
     *
     * @apiUse         DefaultCreateErrorResponse
     *
     */

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @api            {post} /api/v1/tasks/show Show
     * @apiDescription Show Task
     * @apiVersion     0.1.0
     * @apiName        ShowTask
     * @apiGroup       Task
     *
     * @apiParam {Integer}  id                         Task id
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Integer}  [active]                   Is Task active. Available value: {0,1}
     * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse         TaskRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          1,
     *      "project_id":  ["=", [1,2,3]],
     *      "active":      1,
     *      "user_id":     ["=", [1,2,3]],
     *      "assigned_by": ["=", [1,2,3]],
     *      "task_name":   ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "url":         ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         TaskRelationsExample
     *
     * @apiSuccess {Object}   Task                Task
     * @apiSuccess {Integer}  Task.id             Task id
     * @apiSuccess {Integer}  Task.project_id     Task Project id
     * @apiSuccess {Integer}  Task.user_id        Task User id
     * @apiSuccess {Integer}  Task.active         Task active status
     * @apiSuccess {String}   Task.task_name      Task name
     * @apiSuccess {String}   Task.description    Task description
     * @apiSuccess {String}   Task.url            Task url
     * @apiSuccess {String}   Task.created_at     Task date time of create
     * @apiSuccess {String}   Task.updated_at     Task date time of update
     * @apiSuccess {String}   Task.deleted_at     Task date time of delete
     * @apiSuccess {Object[]} Task.time_intervals Task Users
     * @apiSuccess {Object[]} Task.user           Task User object
     * @apiSuccess {Object[]} Task.assigned       Task assigned User object
     * @apiSuccess {Object[]} Task.project        Task Project
     *
     * @apiUse         DefaultShowErrorResponse
     *
     */

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @api            {post} /api/v1/tasks/edit Edit
     * @apiDescription Edit Task
     * @apiVersion     0.1.0
     * @apiName        EditTask
     * @apiGroup       Task
     *
     * @apiParam {Integer}  id          Task id
     * @apiParam {Integer}  project_id  Task Project
     * @apiParam {String}   task_name   Task Name
     * @apiParam {String}   description Task Description
     * @apiParam {String}   [url]       Task Url
     * @apiParam {Integer}  active      Is Task active. Available value: {0,1}
     * @apiParam {Integer}  user_id     Task User
     * @apiParam {Integer}  assigned_by User who assigned task
     * @apiParam {Integer}  priority_id Task Priority ID
     * @apiUse         TaskRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          1,
     *      "project_id":  2,
     *      "active":      1,
     *      "user_id":     3,
     *      "assigned_by": 2,
     *      "task_name":   "lorem",
     *      "description": "test",
     *      "url":         "url",
     *      "priority_id": 1
     *  }
     * @apiUse         TaskRelationsExample
     *
     * @apiSuccess {Object}   res                Task object
     * @apiSuccess {Integer}  res.id             Task ID
     * @apiSuccess {Integer}  res.project_id     Task Project ID
     * @apiSuccess {Integer}  res.user_id        Task User ID
     * @apiSuccess {Integer}  res.active         Task active status
     * @apiSuccess {String}   res.task_name      Task name
     * @apiSuccess {String}   res.description    Task description
     * @apiSuccess {String}   res.url            Task url
     * @apiSuccess {String}   res.created_at     Task date time of create
     * @apiSuccess {String}   res.updated_at     Task date time of update
     * @apiSuccess {String}   res.deleted_at     Task date time of delete
     *
     * @apiUse         DefaultEditErrorResponse
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/tasks/remove Destroy
     * @apiDescription Destroy Task
     * @apiVersion     0.1.0
     * @apiName        DestroyTask
     * @apiGroup       Task
     *
     * @apiParam {String} id Task Id
     *
     * @apiUse         DefaultDestroyRequestExample
     * @apiUse         DefaultDestroyResponse
     *
     */

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @api            {post} /api/v1/tasks/dashboard Dashboard
     * @apiDescription Display task for dashboard
     * @apiVersion     0.1.0
     * @apiName        DashboardTask
     * @apiGroup       Task
     *
     * @apiParam {Integer}  [id]          `QueryParam` Task ID
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Integer}  [active]                   Active/Inactive Task. Available value: {0,1}
     * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse         TaskRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          [">", 1]
     *      "project_id":  ["=", [1,2,3]],
     *      "active":      1,
     *      "user_id":     ["=", [1,2,3]],
     *      "assigned_by": ["=", [1,2,3]],
     *      "task_name":   ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "url":         ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         TaskRelationsExample
     *
     * @apiSuccess {Object[]} array                       Tasks
     * @apiSuccess {Object}   array.object                Task object
     * @apiSuccess {Integer}  array.object.id             Task ID
     * @apiSuccess {Integer}  array.object.project_id     Task Project ID
     * @apiSuccess {Integer}  array.object.user_id        Task User ID
     * @apiSuccess {Integer}  array.object.active         Task active status
     * @apiSuccess {String}   array.object.task_name      Task name
     * @apiSuccess {String}   array.object.description    Task description
     * @apiSuccess {String}   array.object.url            Task url
     * @apiSuccess {String}   array.object.created_at     Task date time of create
     * @apiSuccess {String}   array.object.updated_at     Task date time of update
     * @apiSuccess {String}   array.object.deleted_at     Task date time of delete
     * @apiSuccess {Time}     array.object.total_time     Task total time
     * @apiSuccess {Object[]} array.object.time_intervals Task TimeIntervals
     * @apiSuccess {Object}   array.object.user           Task User object
     * @apiSuccess {Object[]} array.object.assigned       Task assigned User
     * @apiSuccess {Object}   array.object.project        Task Project
     *
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
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

    /**
     * Returns users activity info for task.
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function activity(Request $request): JsonResponse
    {
        $itemId = is_int($request->get('id')) ? $request->get('id') : false;

        if (!$itemId) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'error' => 'Validation fail',
                    'reason' => 'Id invalid',
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
                    'error' => 'Access denied',
                    'reason' => 'User haven\'t access to this task',
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

    /**
     * @param  bool  $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $user = Auth::user();
        $user_id = $user->id;
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can($user, 'tasks', 'full_access');
        $action_method = Route::getCurrentRoute()->getActionMethod();

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
