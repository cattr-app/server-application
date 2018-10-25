<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Task;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Filter;
use DateTime;
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
            'project_id'  => 'required',
            'task_name'   => 'required',
            'active'      => 'required',
            'user_id'     => 'required',
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
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return ['priority'];
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
     * @api {post} /api/v1/tasks/list List
     * @apiDescription Get list of Tasks
     * @apiVersion 0.1.0
     * @apiName GetTaskList
     * @apiGroup Task
     *
     * @apiParam {Integer}  [id]          `QueryParam` Task ID
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Boolean}  [active]                   Active/Inactive Task
     * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {DateTime} [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {DateTime} [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse TaskRelations
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
     * @apiUse TaskRelationsExample
     *
     * @apiSuccess {Object[]} TaskList                     Tasks (Array of objects)
     * @apiSuccess {Object}   TaskList.Task                Task object
     * @apiSuccess {Integer}  TaskList.Task.id             Task's ID
     * @apiSuccess {Integer}  TaskList.Task.project_id     Task's Project ID
     * @apiSuccess {Integer}  TaskList.Task.user_id        Task's User ID
     * @apiSuccess {Integer}  TaskList.Task.active         Task's active status
     * @apiSuccess {String}   TaskList.Task.task_name      Task's name
     * @apiSuccess {String}   TaskList.Task.description    Task's description
     * @apiSuccess {String}   TaskList.Task.url            Task's url
     * @apiSuccess {DateTime} TaskList.Task.created_at     Task's date time of create
     * @apiSuccess {DateTime} TaskList.Task.updated_at     Task's date time of update
     * @apiSuccess {DateTime} TaskList.Task.deleted_at     Task's date time of delete
     * @apiSuccess {Object[]} TaskList.Task.time_intervals Task's User (Array of objects)
     * @apiSuccess {Object[]} TaskList.Task.user           Task's User object
     * @apiSuccess {Object[]} TaskList.Task.assigned       Task's assigned User object
     * @apiSuccess {Object[]} TaskList.Task.project        Task's Project object
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/create Create
     * @apiDescription Create Task
     * @apiVersion 0.1.0
     * @apiName CreateTask
     * @apiGroup Task
     *
     * @apiParam {Integer} [project_id]  Task Project
     * @apiParam {String}  [task_name]   Task Name
     * @apiParam {String}  [description] Task Description
     * @apiParam {String}  url           Task Url
     * @apiParam {Boolean} [active]      Active/Inactive Task
     * @apiParam {Integer} [user_id]     Task's User
     * @apiParam {Integer} [assigned_by] User who assigned task
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "project_id":"163",
     *      "task_name":"retr",
     *      "description":"fdgfd",
     *      "active":1,
     *      "user_id":"3",
     *      "assigned_by":"1",
     *      "url":"URL"
     *  }
     *
     * @apiSuccess {Object}   res                Task object
     * @apiSuccess {Integer}  res.id             Task's ID
     * @apiSuccess {Integer}  res.project_id     Task's Project ID
     * @apiSuccess {Integer}  res.user_id        Task's User ID
     * @apiSuccess {Integer}  res.active         Task's active status
     * @apiSuccess {String}   res.task_name      Task's name
     * @apiSuccess {String}   res.description    Task's description
     * @apiSuccess {String}   res.url            Task's url
     * @apiSuccess {DateTime} res.created_at     Task's date time of create
     * @apiSuccess {DateTime} res.updated_at     Task's date time of update
     *
     * @apiUse DefaultCreateErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/show Show
     * @apiDescription Show Task
     * @apiVersion 0.1.0
     * @apiName ShowTask
     * @apiGroup Task
     *
     * @apiParam {Integer}  id                         Task ID
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Boolean}  [active]                   Active/Inactive Task
     * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {DateTime} [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {DateTime} [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse TaskRelations
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
     * @apiUse TaskRelationsExample
     *
     * @apiSuccess {Object}   Task                Task object
     * @apiSuccess {Integer}  Task.id             Task's ID
     * @apiSuccess {Integer}  Task.project_id     Task's Project ID
     * @apiSuccess {Integer}  Task.user_id        Task's User ID
     * @apiSuccess {Integer}  Task.active         Task's active status
     * @apiSuccess {String}   Task.task_name      Task's name
     * @apiSuccess {String}   Task.description    Task's description
     * @apiSuccess {String}   Task.url            Task's url
     * @apiSuccess {String}   Task.created_at     Task's date time of create
     * @apiSuccess {String}   Task.updated_at     Task's date time of update
     * @apiSuccess {String}   Task.deleted_at     Task's date time of delete
     * @apiSuccess {Object[]} Task.time_intervals Task's User (Array of objects)
     * @apiSuccess {Object[]} Task.user           Task's User object
     * @apiSuccess {Object[]} Task.assigned       Task's assigned User object
     * @apiSuccess {Object[]} Task.project        Task's Project object
     *
     * @apiUse DefaultShowErrorResponse
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/edit Edit
     * @apiDescription Edit Task
     * @apiVersion 0.1.0
     * @apiName EditTask
     * @apiGroup Task
     *
     * @apiParam {Integer}  id          Task ID
     * @apiParam {Integer}  project_id  Task Project
     * @apiParam {String}   task_name   Task Name
     * @apiParam {String}   description Task Description
     * @apiParam {String}   [url]       Task Url
     * @apiParam {Boolean}  active      Active/Inactive Task
     * @apiParam {Integer}  user_id     Task's User
     * @apiParam {Integer}  assigned_by User who assigned task
     * @apiUse TaskRelations
     *
     * @apiParamExample {json} Simple-Request Example
     *  {
     *      "id":          1,
     *      "project_id":  2,
     *      "active":      1,
     *      "user_id":     3,
     *      "assigned_by": 2,
     *      "task_name":   "lorem",
     *      "description": "test",
     *      "url":         "url"
     *  }
     * @apiUse TaskRelationsExample
     *
     * @apiSuccess {Object}   res                Task object
     * @apiSuccess {Integer}  res.id             Task's ID
     * @apiSuccess {Integer}  res.project_id     Task's Project ID
     * @apiSuccess {Integer}  res.user_id        Task's User ID
     * @apiSuccess {Integer}  res.active         Task's active status
     * @apiSuccess {String}   res.task_name      Task's name
     * @apiSuccess {String}   res.description    Task's description
     * @apiSuccess {String}   res.url            Task's url
     * @apiSuccess {String}   res.created_at     Task's date time of create
     * @apiSuccess {String}   res.updated_at     Task's date time of update
     * @apiSuccess {String}   res.deleted_at     Task's date time of delete
     *
     * @apiUse DefaultEditErrorResponse
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/remove Destroy
     * @apiDescription Destroy Task
     * @apiVersion 0.1.0
     * @apiName DestroyTask
     * @apiGroup Task
     *
     * @apiParam {String} id Task Id
     *
     * @apiUse DefaultDestroyRequestExample
     * @apiUse DefaultDestroyResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/dashboard Dashboard
     * @apiDescription Display task for dashboard
     * @apiVersion 0.1.0
     * @apiName DashboardTask
     * @apiGroup Task
     *
     * @apiParam {Integer}  [id]          `QueryParam` Task ID
     * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
     * @apiParam {String}   [task_name]   `QueryParam` Task Name
     * @apiParam {String}   [description] `QueryParam` Task Description
     * @apiParam {String}   [url]         `QueryParam` Task Url
     * @apiParam {Boolean}  [active]                   Active/Inactive Task
     * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
     * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
     * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
     * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
     * @apiUse TaskRelations
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
     * @apiUse TaskRelationsExample
     *
     * @apiSuccess {Object[]} Array                       Array of objects
     * @apiSuccess {Object}   Array.object                Task object
     * @apiSuccess {Integer}  Array.object.id             Task's ID
     * @apiSuccess {Integer}  Array.object.project_id     Task's Project ID
     * @apiSuccess {Integer}  Array.object.user_id        Task's User ID
     * @apiSuccess {Integer}  Array.object.active         Task's active status
     * @apiSuccess {String}   Array.object.task_name      Task's name
     * @apiSuccess {String}   Array.object.description    Task's description
     * @apiSuccess {String}   Array.object.url            Task's url
     * @apiSuccess {String}   Array.object.created_at     Task's date time of create
     * @apiSuccess {String}   Array.object.updated_at     Task's date time of update
     * @apiSuccess {String}   Array.object.deleted_at     Task's date time of delete
     * @apiSuccess {Time}     Array.object.total_time     Task's total time
     * @apiSuccess {Object[]} Array.object.time_intervals Task's User (Array of objects)
     * @apiSuccess {Object[]} Array.object.user           Task's User object
     * @apiSuccess {Object[]} Array.object.assigned       Task's assigned User object
     * @apiSuccess {Object[]} Array.object.project        Task's Project object
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $timezone = Auth::user()->timezone;
        if (!$timezone) {
            $timezone = 'UTC';
        }

        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['timeIntervals.user_id'] = $request->get('user_id') : False;
        $compareDate = Carbon::today($timezone)->setTimezone('UTC')->toDateTimeString();
        $filters['timeIntervals.start_at'] = ['>=', [$compareDate]];
        unset($filters['user_id']);

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                $filters ?: []
            )
        );

        $items = $itemsQuery->with(['timeIntervals' => function ($q) use ($compareDate) {
            /** @var Builder $q */
            $q->where('start_at', '>=', $compareDate);
        }])->get()->toArray();

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
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'tasks', 'full_access');
        $relations_access = Role::can(Auth::user(), 'users', 'relations');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $user_tasks_id = collect(Auth::user()->tasks)->pluck('id');
        $tasks_id = collect([$user_tasks_id])->collapse();

        if ($project_relations_access) {
            $attached_task_id_to_project = collect(Auth::user()->projects)->flatMap(function ($project) {
                return collect($project->tasks)->pluck('id');
            });
            $tasks_id = collect([$attached_task_id_to_project])->collapse();
        }

        if ($relations_access) {
            $attached_tasks_id_to_users = collect(Auth::user()->attached_users)->flatMap(function($user) {
                return collect($user->tasks)->pluck('id');
            });
            $tasks_id = collect([$tasks_id, $attached_tasks_id_to_users])->collapse()->unique();
        }

        /** edit and remove only for directly related users's project's task */
        if ($action_method === 'edit' || $action_method === 'remove') {
            $attached_projects_id = collect(Auth::user()->projects)->pluck('id');
            $user_project_tasks = collect(Auth::user()->tasks)->filter(function ($val, $key) use($attached_projects_id) {
               return collect($attached_projects_id)->containsStrict($val['project_id']);
            });
            $tasks_id = collect($user_project_tasks)->pluck('id');
        }

        $query->whereIn('tasks.id', $tasks_id);
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
