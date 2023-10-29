<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\DestroyTaskRequest;
use App\Http\Requests\Task\EditTaskRequest;
use App\Http\Requests\Task\ListTaskRequest;
use App\Http\Requests\Task\ShowTaskRequest;
use App\Jobs\SaveTaskEditHistory;
use App\Models\Priority;
use App\Models\Project;
use Exception;
use Filter;
use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use CatEvent;
use Illuminate\Support\Arr;
use Settings;
use Throwable;

class TaskController extends ItemController
{
    protected const MODEL = Task::class;

    /**
     * @api             {post} /tasks/list List
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
     *      "description": "Et qui sed qui vero quis.
     *                      Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
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
     * @param ListTaskRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListTaskRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /tasks/edit Edit
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
     * @apiParam {Array}    users        Task Users
     * @apiParam {Integer}  priority_id  Priority ID
     *
     * @apiUse         TaskParams
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "project_id": 2,
     *    "active": 1,
     *    "users": [3],
     *    "assigned_by": 2,
     *    "task_name": "lorem",
     *    "description": "test",
     *    "url": "url",
     *    "priority_id": 1
     *  }
     *
     * @apiSuccess {Object}   res      Task
     *
     * @apiUse         TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 2,
     *      "project_id": 1,
     *      "task_name": "Delectus.",
     *      "description": "Et qui sed qui vero quis.
     *                      Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *      "active": 1,
     *      "users": [],
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
    public function edit(EditTaskRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $requestData) {
                if (!empty($requestData['priority_id'])) {
                    return $requestData;
                }

                if (($project = Project::findOrFail($requestData['project_id'])) && !empty($project->default_priority_id)) {
                    $requestData['priority_id'] = $project->default_priority_id;
                    return $requestData;
                }

                if ($priority = Settings::scope('core')->get('default_priority_id')) {
                    $requestData['priority_id'] = $priority;
                    return $requestData;
                }

                $requestData['priority_id'] = Priority::firstOrFail()->id;

                return $requestData;
            }
        );

        CatEvent::listen(Filter::getAfterActionEventName(), static function (Task $data) use ($request) {
            $oldUsers = $data->users()->select('id', 'full_name');
            $changes = $data->users()->sync($request->get('users'));
            if (!empty($changes['attached']) || !empty($changes['detached']) || !empty($changes['updated'])) {
                SaveTaskEditHistory::dispatch(
                    $data,
                    $request->user(),
                    [
                        'users' => (string)User::withoutGlobalScopes()
                            ->whereIn('id', $request->get('users'))
                            ->select(['id', 'full_name'])
                    ],
                    [
                        'users' => json_encode($oldUsers),
                    ]
                );
            }
            SaveTaskEditHistory::dispatch($data, request()->user());
        });

        return $this->_edit($request);
    }

    /**
     * @param CreateTaskRequest $request
     * @return JsonResponse
     *
     * @throws Throwable
     * @api             {post} /tasks/create Create
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
     * @apiParam {Array}    users        Users
     * @apiParam {Integer}  assigned_by  User who assigned task
     * @apiParam {Integer}  priority_id  Priority ID
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "project_id":"163",
     *    "task_name":"retr",
     *    "description":"fdgfd",
     *    "active":1,
     *    "users":[3],
     *    "assigned_by":"1",
     *    "url":"URL",
     *    "priority_id": 1
     *  }
     *
     * @apiSuccess {Object}   res      Task
     *
     * @apiUse TaskObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 2,
     *      "project_id": 1,
     *      "task_name": "Delectus.",
     *      "description": "Et qui sed qui vero quis.
     *                      Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
     *      "active": 1,
     *      "users": [],
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
    public function create(CreateTaskRequest $request): JsonResponse
    {
        CatEvent::listen(
            Filter::getAfterActionEventName(),
            static fn(Task $task) => $task->users()->sync($request->get('users'))
        );

        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $requestData) {
                if (!empty($requestData['priority_id'])) {
                    return $requestData;
                }

                if (($project = Project::findOrFail($requestData['project_id'])) && !empty($project->default_priority_id)) {
                    $requestData['priority_id'] = $project->default_priority_id;
                    return $requestData;
                }

                if ($priority = Settings::scope('core')->get('default_priority_id')) {
                    $requestData['priority_id'] = $priority;
                    return $requestData;
                }

                $requestData['priority_id'] = Priority::firstOrFail()->id;

                return $requestData;
            }
        );

        return $this->_create($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /tasks/remove Destroy
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
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function destroy(DestroyTaskRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @throws Exception
     * @api             {get,post} /tasks/count Count
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
     * @apiSuccess {String}   total    Amount of tasks that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 2
     *
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function count(ListTaskRequest $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /tasks/show Show
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
     *    "description": "Et qui sed qui vero quis.
     *                    Vitae corporis sapiente saepe dolor rerum. Eligendi commodi quia rerum ut.",
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
    public function show(ShowTaskRequest $request): JsonResponse
    {
        Filter::listen(Filter::getSuccessResponseFilterName(), static function ($task) {
            $task['total_spent_time'] = 0;
            $task['workers'] = [];

            DB::table('time_intervals AS i')
                ->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
                ->join('users AS u', 'i.user_id', '=', 'u.id')
                ->select(
                    'i.user_id',
                    'u.full_name',
                    'i.task_id',
                    'i.start_at',
                    'i.end_at',
                    DB::raw('SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration')
                )
                ->whereNull('i.deleted_at')
                ->where('task_id', $task['id'])
                ->groupBy('i.user_id')
                ->get()
                ->each(static function ($worker) use (&$task) {
                    $task['total_spent_time'] += $worker->duration;
                    $task['workers'][] = $worker;
                });

            return $task;
        });

        return $this->_show($request);
    }
}
