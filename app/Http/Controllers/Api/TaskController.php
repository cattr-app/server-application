<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskRelationType;
use App\Exceptions\Entities\TaskRelationException;
use App\Http\Middleware\RegisterModulesEvents;
use App\Http\Requests\Task\CalendarRequest;
use App\Http\Requests\Task\CreateRelationRequest;
use App\Http\Requests\Task\CreateTaskRequest;
use App\Http\Requests\Task\DestroyTaskRequest;
use App\Http\Requests\Task\EditTaskRequest;
use App\Http\Requests\Task\ListTaskRequest;
use App\Http\Requests\Task\ShowTaskRequest;
use App\Http\Requests\Task\DestroyRelationRequest;
use App\Jobs\SaveTaskEditHistory;
use App\Models\Priority;
use App\Models\Project;
use Exception;
use Filter;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Container\BindingResolutionException;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use CatEvent;
use Illuminate\Http\Response;
use MessagePack\MessagePack;
use DB;
use Settings;
use Throwable;

class TaskController extends ItemController
{
    protected const MODEL = Task::class;

    /**
     * @api             {post} /tasks/list List
     * @apiDescription  Get list of Tasks
     *
     * @apiVersion      4.0.0
     * @apiName         List
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_list
     * @apiPermission   tasks_full_access
     *
     *
     * @apiSuccess {Integer} id Task ID.
     * @apiSuccess {Integer} project_id Project ID associated with the task.
     * @apiSuccess {Integer} [project_phase_id] ID of the project phase (if any).
     * @apiSuccess {String} task_name Name of the task.
     * @apiSuccess {String} description Description of the task.
     * @apiSuccess {Integer} assigned_by ID of the user who assigned the task.
     * @apiSuccess {String} url URL of the task.
     * @apiSuccess {String} created_at Task creation date in ISO format.
     * @apiSuccess {String} updated_at Task update date in ISO format.
     * @apiSuccess {String} [deleted_at] Deletion date if the task is soft-deleted.
     * @apiSuccess {Integer} priority_id Priority level of the task.
     * @apiSuccess {Boolean} important Indicates whether the task is marked as important.
     * @apiSuccess {String} [start_date] Start date of the task (if set).
     * @apiSuccess {String} [due_date] Due date of the task (if set).
     * @apiSuccess {Integer} status_id Status ID of the task.
     * @apiSuccess {Integer} relative_position Relative position of the task in the list.
     * @apiSuccess {Integer} [project_milestone_id] Project milestone ID if linked.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *     {
     *       "id": 11,
     *       "project_id": 2,
     *       "project_phase_id": null,
     *       "task_name": "Qui velit fugiat magni accusantium.",
     *       "description": "Dignissimos praesentium voluptatibus velit et velit tenetur...",
     *       "assigned_by": 3,
     *       "url": null,
     *       "created_at": "2023-10-26T10:26:42.000000Z",
     *       "updated_at": "2023-10-26T10:26:42.000000Z",
     *       "deleted_at": null,
     *       "priority_id": 3,
     *       "important": 1,
     *       "start_date": null,
     *       "due_date": null,
     *       "status_id": 2,
     *       "relative_position": 11,
     *       "project_milestone_id": null
     *     },
     *     ...
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
     * @apiVersion      4.0.0
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
     *
     * @apiParamExample {json} Request Example:
     * {
     *   "start_date": null,
     *   "due_date": null,
     *   "id": 54,
     *   "project_id": 159,
     *   "project_phase_id": null,
     *   "task_name": "Quo consequatur mollitia nam.",
     *   "description": "<p>Aut iure minima vero voluptates nisi placeat. Distinctio fuga aut sit quia sequi. Cupiditate tenetur sit ut voluptatem ratione culpa. Voluptatibus id perspiciatis ipsa quas cumque laudantium repudiandae.</p>",
     *   "assigned_by": 7,
     *   "url": null,
     *   "created_at": "2023-11-07T13:23:46.000000Z",
     *   "updated_at": "2023-11-07T13:23:46.000000Z",
     *   "deleted_at": null,
     *   "priority_id": 2,
     *   "important": 0,
     *   "project_milestone_id": null,
     *   "status_id": 2,
     *   "relative_position": 54,
     *   "estimate": null,
     *   "total_spent_time": null,
     *   "total_offset": null,
     *   "priority": {
     *     "id": 2,
     *     "name": "Normal",
     *     "created_at": "2023-10-26T10:26:17.000000Z",
     *     "updated_at": "2024-06-21T10:06:50.000000Z",
     *     "color": "#49E637"
     *   },
     *   "project": {
     *     "id": 159,
     *     "company_id": 1,
     *     "name": "Voluptas ab et ea.",
     *     "description": "Cum aut sunt in fuga quia. Similique autem et quod qui eveniet omnis consequatur. Molestias tenetur est tempora tenetur.",
     *     "deleted_at": null,
     *     "created_at": "2023-11-07T13:23:45.000000Z",
     *     "updated_at": "2023-11-07T13:23:45.000000Z",
     *     "important": 0,
     *     "source": "internal",
     *     "default_priority_id": null,
     *     "screenshots_state": 1
     *   },
     *   "phase": null,
     *   "parents": [],
     *   "children": [],
     *   "users": [7],
     *   "status": {
     *     "id": 2,
     *     "name": "Closed",
     *     "active": false,
     *     "created_at": "2024-02-28T16:47:00.000000Z",
     *     "updated_at": "2024-06-08T16:17:04.000000Z",
     *     "color": null,
     *     "order": 4
     *   },
     *   "changes": [],
     *   "comments": [
     *     {
     *       "id": 22,
     *       "task_id": 54,
     *       "user_id": 1,
     *       "content": "lll",
     *       "created_at": "2024-08-15T14:49:16.000000Z",
     *       "updated_at": "2024-08-15T14:49:16.000000Z",
     *       "deleted_at": null,
     *       "user": {
     *         "id": 1,
     *         "full_name": "Admin",
     *         "email": "admin@cattr.app",
     *         ...
     *       }
     *     },
     *     ...
     *   ],
     *   "workers": []
     * }
     *
     * @apiSuccess {Integer} id Task ID.
     * @apiSuccess {Integer} project_id Project ID associated with the task.
     * @apiSuccess {Integer} [project_phase_id] ID of the project phase (if any).
     * @apiSuccess {String} task_name Name of the task.
     * @apiSuccess {String} description Description of the task.
     * @apiSuccess {Integer} assigned_by ID of the user who assigned the task.
     * @apiSuccess {String} url URL of the task.
     * @apiSuccess {String} created_at Task creation date in ISO format.
     * @apiSuccess {String} updated_at Task update date in ISO format.
     * @apiSuccess {String} [deleted_at] Deletion date if the task is soft-deleted.
     * @apiSuccess {Integer} priority_id Priority level of the task.
     * @apiSuccess {Boolean} important Indicates whether the task is marked as important.
     * @apiSuccess {String} [start_date] Start date of the task (if set).
     * @apiSuccess {String} [due_date] Due date of the task (if set).
     * @apiSuccess {Integer} status_id Status ID of the task.
     * @apiSuccess {Integer} relative_position Relative position of the task in the list.
     *
     * @apiSuccessExample {json} Success Response:
     * {
     *     "id": 54,
     *     "project_id": 159,
     *     "project_phase_id": null,
     *     "task_name": "Quo consequatur mollitia nam.",
     *     "description": "<p>Aut iure minima vero voluptates nisi placeat. Distinctio fuga aut sit quia sequi...</p>",
     *     "assigned_by": 7,
     *     "url": null,
     *     "created_at": "2023-11-07T13:23:46.000000Z",
     *     "updated_at": "2024-08-15T15:39:53.000000Z",
     *     "deleted_at": null,
     *     "priority_id": 2,
     *     "important": 0,
     *     "start_date": null,
     *     "project_milestone_id": null,
     *     "status_id": 2,
     *     "relative_position": 54,
     *     "estimate": null,
     *     "due_date": null
     * }
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
                if (empty($requestData['due_date'])) {
                    $requestData['due_date'] = null;
                }

                if (isset($requestData['estimate']) && $requestData['estimate'] <= 0) {
                    $requestData['estimate'] = null;
                }

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

        CatEvent::listen(Filter::getBeforeActionEventName(), static function (Task $task, array $requestData) {
            if (isset($requestData['start_date'])) {
                throw_if(
                    $task->ancestors()
                        ->where(fn(Builder $q) => $q
                            ->where('due_date', '>', $requestData['start_date'])
                            ->orWhere('start_date', '>', $requestData['start_date']))
                        ->exists(),
                    new TaskRelationException(TaskRelationException::CANNOT_START_BEFORE_PARENT_ENDS)
                );
            } elseif (isset($requestData['due_date'])) {
                throw_if(
                    $task->ancestors()
                        ->where(fn(Builder $q) => $q
                            ->where('due_date', '>', $requestData['due_date'])
                            ->orWhere('start_date', '>', $requestData['due_date']))
                        ->exists(),
                    new TaskRelationException(TaskRelationException::CANNOT_START_BEFORE_PARENT_ENDS)
                );
            } else {
                return;
            }
            $newDate = $requestData['due_date'] ?? $requestData['start_date'];

            $nearestChildStartDate = $task->descendants()
                ->where('start_date', '<', $newDate)
                ->orderBy('start_date')
                ->first()?->start_date;

            $nearestChildDueDate = $task->descendants()
                ->where('due_date', '<', $newDate)
                ->orderBy('due_date')
                ->first()?->due_date;

            if (is_null($nearestChildStartDate) && is_null($nearestChildDueDate)) { // no date overlap
                return;
            }
            if (is_null($nearestChildStartDate)) {
                $nearestChildDate = $nearestChildDueDate;
            } elseif (is_null($nearestChildDueDate)) {
                $nearestChildDate = $nearestChildStartDate;
            } else {
                $nearestChildDate = min($nearestChildStartDate, $nearestChildDueDate);
            }

            $delta = $nearestChildDate->diffInDays($newDate, false);

            dispatch(fn() => Task::withInitialQueryConstraint(function (Builder $query) use ($newDate) {
                $query->where(fn(Builder $q) => $q
                    ->where('start_date', '<', $newDate)
                    ->orWhere('due_date', '<', $newDate));
            }, fn() => $task->descendants()->groupBy('id')->lazyById()->each(
                static function (Task $child) use ($delta) {
                    $child->start_date = $child->start_date?->addDays($delta);
                    $child->due_date = $child->due_date?->addDays($delta);
                    $child->save();
                }
            )));
        });

        $taskBeforeChanges = null;
        CatEvent::listen(Filter::getBeforeActionEventName(), static function (Task $task) use (&$taskBeforeChanges) {
            $taskBeforeChanges = $task->getOriginal();
            $taskBeforeChanges['_old_phase_name'] = $task->phase?->name;
            $taskBeforeChanges['_old_users'] = $task->users()->select('id', 'full_name')->get()->map(fn($item)=>$item->full_name)->join(', ');
        });

        CatEvent::listen(Filter::getAfterActionEventName(), static function (Task $data) use (&$taskBeforeChanges, $request) {
            $oldUsers = $taskBeforeChanges['_old_users'];
            $changes = $data->users()->sync($request->get('users'));
            if (!empty($changes['attached']) || !empty($changes['detached']) || !empty($changes['updated'])) {
                SaveTaskEditHistory::dispatch(
                    $data,
                    $request->user(),
                    [
                        'users' => User::withoutGlobalScopes()
                            ->whereIn('id', $request->get('users'))
                            ->select(['id', 'full_name'])->get()->map(fn($item)=>$item->full_name)->join(', ')
                    ],
                    [
                        'users' => $oldUsers,
                    ]
                );
            }
            SaveTaskEditHistory::dispatch($data, request()->user(), null, $taskBeforeChanges);
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
     * @apiVersion      4.0.0
     * @apiName         Create
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_create
     * @apiPermission   tasks_full_access
     *
     * @apiParam {String}  start_date          Start date of the task (in YYYY-MM-DD format).
     * @apiParam {String}  due_date           Due date of the task (in YYYY-MM-DD format).
     * @apiParam {Array}   users              List of user IDs assigned to the task.
     * @apiParam {Integer} [project_phase_id] ID of the project phase (if any).
     * @apiParam {Integer} project_id         Project ID to which the task belongs.
     * @apiParam {String}  task_name          Name of the task.
     * @apiParam {String}  description        Description of the task in HTML format.
     * @apiParam {Boolean} important          Indicates if the task is marked as important.
     * @apiParam {Integer} priority_id        Priority level ID of the task.
     * @apiParam {Integer} status_id          Status ID of the task.
     * @apiParamExample {json} Simple Request Example
     * {
     *   "start_date": "2024-08-14",
     *   "due_date": "2024-08-16",
     *   "users": [
     *     1,
     *     6,
     *     7
     *   ],
     *   "project_phase_id": null,
     *   "project_id": 2,
     *   "task_name": "test",
     *   "description": "<p>test</p>",
     *   "important": true,
     *   "priority_id": 2,
     *   "status_id": 5
     * }
     * @apiSuccess {Integer} id Task ID.
     * @apiSuccess {Integer} project_id Project ID associated with the task.
     * @apiSuccess {Integer} [project_phase_id] ID of the project phase (if any).
     * @apiSuccess {String}  task_name Name of the task.
     * @apiSuccess {String}  description Description of the task.
     * @apiSuccess {Boolean} important Indicates whether the task is marked as important.
     * @apiSuccess {Integer} priority_id Priority level ID of the task.
     * @apiSuccess {Integer} status_id Status ID of the task.
     * @apiSuccess {String}  start_date Start date of the task in ISO format.
     * @apiSuccess {String}  due_date Due date of the task in ISO format.
     * @apiSuccess {String}  created_at Task creation date in ISO format.
     * @apiSuccess {String}  updated_at Task update date in ISO format.
     *
     * @apiSuccessExample {json} Success Response:
     * {
     *     "project_id": 2,
     *     "project_phase_id": null,
     *     "task_name": "test",
     *     "description": "<p>test</p>",
     *     "important": 1,
     *     "priority_id": 2,
     *     "status_id": 5,
     *     "start_date": "2024-08-14T00:00:00.000000Z",
     *     "due_date": "2024-08-16T00:00:00.000000Z",
     *     "updated_at": "2024-08-16T09:07:47.000000Z",
     *     "created_at": "2024-08-16T09:07:47.000000Z",
     *     "id": 116
     * }
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
            static fn (Task $task) => $task->users()->sync($request->get('users'))
        );

        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $requestData) {
                if (empty($requestData['due_date'])) {
                    $requestData['due_date'] = null;
                }

                if (isset($requestData['estimate']) && $requestData['estimate'] <= 0) {
                    $requestData['estimate'] = null;
                }

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
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) {
            $maxPosition = Task::max('relative_position');
            $requestData['relative_position'] = $maxPosition + 1;
            return $requestData;
        });

        return $this->_create($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /tasks/remove Destroy
     * @apiDescription  Destroy Task
     *
     * @apiVersion      4.0.0
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
     *   "id": 54
     * }
     *
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     * HTTP/1.1 204 No Content
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
     * @apiVersion      4.0.0
     * @apiName         Count
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_count
     * @apiPermission   tasks_full_access
     *
     * @apiSuccess {Integer}   total    Amount of tasks that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 2
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
     * @apiVersion      4.0.0
     * @apiName         Show
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_show
     * @apiPermission   tasks_full_access
     *
     * @apiParamExample {json} Request Example:
     * {
     *   "id": "55",
     *   "with": [
     *     "priority",
     *     "project",
     *     "phase:id,name",
     *     "parents",
     *     "children",
     *     "users",
     *     "status",
     *     "changes",
     *     "changes.user",
     *     "comments",
     *     "comments.user",
     *     "workers",
     *     "workers.user:id,full_name"
     *   ],
     *   "withSum": [
     *     ["workers as total_spent_time", "duration"],
     *     ["workers as total_offset", "offset"]
     *   ]
     * }
     * @apiSuccess {Integer} id The ID of the task.
     * @apiSuccess {Integer} project_id The ID of the project the task belongs to.
     * @apiSuccess {String} task_name The name of the task.
     * @apiSuccess {String} description The description of the task.
     * @apiSuccess {Boolean} important Indicates if the task is marked as important.
     * @apiSuccess {Object} priority Priority details including ID, name, and color.
     * @apiSuccess {Object} project Project details including ID, name, description, and more.
     * @apiSuccess {Array} users List of users assigned to the task.
     * @apiSuccess {Object} status Status details of the task.
     * @apiSuccess {Array} changes List of changes made to the task.
     * @apiSuccess {Array} comments List of comments related to the task.
     * @apiSuccess {Array} workers List of workers associated with the task.
     * @apiSuccess {String} total_spent_time Sum of the time spent by workers on the task.
     * @apiSuccess {String} total_offset Sum of the offset time for the task.
     *
     * @apiSuccessExample {json} Success Response:
     * {
     *     "id": 55,
     *     "project_id": 159,
     *     "task_name": "Nisi qui ut et.",
     *     "description": "Molestias libero deleniti laboriosam sit libero voluptas aut quibusdam...",
     *     "important": 1,
     *     "priority": {
     *       "id": 3,
     *       "name": "High",
     *       "color": "#D40C0C"
     *     },
     *     "project": {
     *       "id": 159,
     *       "name": "Voluptas ab et ea.",
     *       "description": "Cum aut sunt in fuga quia..."
     *     },
     *     "users": [
     *       {
     *         "id": 7,
     *         "full_name": "Dr. Adaline Toy",
     *         "email": "projectManager1231@example.com"
     *       }
     *     ],
     *     "status": {
     *       "id": 2,
     *       "name": "Closed",
     *       "active": false
     *     },
     *     "changes": [],
     *     "comments": [],
     *     "workers": [],
     *     "total_spent_time": null,
     *     "total_offset": null
     * }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */
    public function show(ShowTaskRequest $request): JsonResponse
    {
        Filter::listen(Filter::getQueryFilterName(), static function ($query) {
            return $query->withAvg('users as efficiency', 'efficiency');
        });

        CatEvent::listen(Filter::getAfterActionEventName(), static function (Task $task) {
            $task->mergeCasts(['forecast_completion_date' => 'date']);

            if ($task->start_date !== null && $task->estimate !== null && $task->efficiency !== null) {
                $task->forecast_completion_date = $task->start_date->addSeconds($task->estimate * $task->efficiency);
            } else {
                $task->forecast_completion_date = null;
            }
        });

        return $this->_show($request);
    }

    /**
     * @throws Throwable
     * @api             {post} /tasks/create-relation Create Relation
     * @apiDescription  Creates a relation between two tasks, ensuring they belong to the same project and no cyclic dependencies exist.
     *
     * @apiVersion      4.0.0
     * @apiName         CreateRelation
     * @apiGroup        Task
     *
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_create_relation
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  task_id            The ID of the task.
     * @apiParam {Integer}  related_task_id    The ID of the related task.
     * @apiParam {String="FOLLOWS","PRECEDES"} relation_type The type of the relation between the tasks.
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "task_id": 1,
     *    "related_task_id": 2,
     *    "relation_type": "follows"
     *  }
     *
     *
     * @apiSuccess {Number} id The ID of the related task.
     * @apiSuccess {Number} project_id The project ID of the related task.
     * @apiSuccess {Number} project_phase_id The project phase ID of the related task, if any.
     * @apiSuccess {String} task_name The name of the related task.
     * @apiSuccess {String} description The description of the related task.
     * @apiSuccess {Number} assigned_by The ID of the user who assigned the task.
     * @apiSuccess {String} url The URL of the related task, if any.
     * @apiSuccess {String} created_at The creation timestamp of the related task.
     * @apiSuccess {String} updated_at The last update timestamp of the related task.
     * @apiSuccess {String} deleted_at The deletion timestamp of the related task, if any.
     * @apiSuccess {Number} priority_id The priority ID of the related task.
     * @apiSuccess {Boolean} important Indicates if the task is marked as important.
     * @apiSuccess {String} start_date The start date of the related task, if any.
     * @apiSuccess {Number} project_milestone_id The project milestone ID of the related task, if any.
     * @apiSuccess {Number} status_id The status ID of the related task.
     * @apiSuccess {Number} relative_position The relative position of the related task.
     * @apiSuccess {String} estimate The estimate for the task, if any.
     * @apiSuccess {String} due_date The due date of the related task, if any.
     * @apiSuccess {Object} pivot The pivot data for the relation.
     * @apiSuccess {Number} pivot.child_id The child task ID.
     * @apiSuccess {Number} pivot.parent_id The parent task ID.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 5,
     *      "project_id": 1,
     *      "project_phase_id": null,
     *      "task_name": "Laudantium sapiente voluptas.",
     *      "description": "Quam incidunt nemo",
     *      "assigned_by": 2,
     *      "url": null,
     *      "created_at": "2023-10-26T10:26:27.000000Z",
     *      "updated_at": "2024-06-16T05:13:13.000000Z",
     *      "deleted_at": null,
     *      "priority_id": 1,
     *      "important": 1,
     *      "start_date": null,
     *      "project_milestone_id": null,
     *      "status_id": 4,
     *      "relative_position": 68.8125,
     *      "estimate": null,
     *      "due_date": null,
     *      "pivot": {
     *        "child_id": 1,
     *        "parent_id": 5
     *      }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */
    public function createRelation(CreateRelationRequest $request): JsonResponse
    {
        $requestData = $request->validated();

        $task = Task::find($requestData['task_id']);
        $relatedTask = Task::find($requestData['related_task_id']);
        $relationType = TaskRelationType::tryFrom($requestData['relation_type']);
        throw_if(
            $task->project_id !== $relatedTask->project_id,
            new TaskRelationException(TaskRelationException::NOT_SAME_PROJECT)
        );
        throw_if(
            $task->children()->where('id', $relatedTask->id)->exists()
            || $task->parents()->where('id', $relatedTask->id)->exists(),
            new TaskRelationException(TaskRelationException::ALREADY_EXISTS)
        );
        throw_if(
            match ($relationType) {
                TaskRelationType::FOLLOWS => $task->descendants()->where('id', $relatedTask->id)->exists(),
                TaskRelationType::PRECEDES => $task->ancestors()->where('id', $relatedTask->id)->exists()
            },
            new TaskRelationException(TaskRelationException::CYCLIC)
        );
        match ($relationType) {
            TaskRelationType::FOLLOWS => $task->parents()->attach($relatedTask),
            TaskRelationType::PRECEDES => $task->children()->attach($relatedTask),
        };
        RegisterModulesEvents::broadcastEvent('tasks', 'edit', $task);
        RegisterModulesEvents::broadcastEvent('tasks', 'edit', $relatedTask);
        $relatedTask->pivot = match ($relationType) { // only for frontend update
            TaskRelationType::FOLLOWS => [
                'child_id' => $task->id,
                'parent_id' => $relatedTask->id,
            ],
            TaskRelationType::PRECEDES => [
                'child_id' => $relatedTask->id,
                'parent_id' => $task->id,
            ]
        };

        // move dates after relation attached
        $parent = $relationType === TaskRelationType::FOLLOWS ? $relatedTask : $task;
        $child = $relationType === TaskRelationType::FOLLOWS ? $task : $relatedTask;
        $parentDateKey = $parent->due_date ? 'due_date' : ($parent->start_date ? 'start_date' : null);
        $childDateKey = $child->start_date ? 'start_date' : ($child->due_date ? 'due_date' : null);

        if (is_null($childDateKey) && $parentDateKey) {
            // no date on child - add any available date from parent
            $child->start_date = $parent->$parentDateKey;
            $child->due_date = $parent->$parentDateKey;
            $child->save();
            RegisterModulesEvents::broadcastEvent('gantt', 'updateAll', $child->project);
        } elseif ($parentDateKey && $childDateKey && $child->$childDateKey->lt($parent->$parentDateKey)) {
            // child date is before parent date - move child and its descendants dates
            $delta = $child->$childDateKey->diffInDays($parent->$parentDateKey, false);
            dispatch(function () use ($delta, $child) {
                $child->descendantsAndSelf()->groupBy('id')->lazyById()
                    ->each(static function (Task $child) use ($delta) {
                        $child->start_date = $child->start_date?->addDays($delta);
                        $child->due_date = $child->due_date?->addDays($delta);
                        $child->save();
                    });
                RegisterModulesEvents::broadcastEvent('gantt', 'updateAll', $child->project);
            });
        } else {
            RegisterModulesEvents::broadcastEvent('gantt', 'updateAll', $child->project);
        }

        return responder()->success($relatedTask)->respond();
    }

     /**
     * @throws Throwable
     * @api             {post} /tasks/remove-relation Destroy Relation
     * @apiDescription  Removes a relation between two tasks.
     *
     * @apiVersion      4.0.0
     * @apiName         DestroyRelation
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   tasks_destroy_relation
     * @apiPermission   tasks_full_access
     *
     * @apiParam {Integer}  parent_id   The ID of the parent task.
     * @apiParam {Integer}  child_id    The ID of the child task.
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "parent_id": 1,
     *    "child_id": 2
     *  }
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {Number} status The HTTP status code.
     * @apiSuccess {Null} data No content is returned.
     *
     * @apiError TaskRelationException The specified relation does not exist or another error occurred.
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */
    public function destroyRelation(DestroyRelationRequest $request): JsonResponse
    {
        $requestData = $request->validated();
        $parentTask = Task::find($requestData['parent_id']);
        $parentTask->children()->detach($requestData['child_id']);

        RegisterModulesEvents::broadcastEvent('gantt', 'updateAll', $parentTask->project);
        RegisterModulesEvents::broadcastEvent('tasks', 'edit', $parentTask);
        RegisterModulesEvents::broadcastEvent('tasks', 'edit', Task::find($requestData['child_id']));

        return responder()->success()->respond(204);
    }

    /**
     * @throws BindingResolutionException
     * @api             {get} /offline-sync/download-projects-and-tasks/{user} Download Projects and Tasks
     * @apiDescription  Downloads all projects and tasks associated with a specific user.
     *
     * @apiVersion      4.0.0
     * @apiName         DownloadProjectsAndTasks
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_download_projects_tasks
     * @apiPermission   users_full_access
     *
     * @apiParam {Integer}  user   The ID of the user.
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {Number} status The HTTP status code.
     * @apiSuccess {File} data A binary file containing the packed projects and tasks data.
     * @apiSuccess {String} Content-type The content type of the file, which is `application/octet-stream`.
     * @apiSuccess {String} Content-Disposition The content disposition of the file, indicating an attachment with the filename `ProjectsAndTasks.cattr`.
     *
     *
     * @apiError UserNotFound The user with the specified ID was not found.
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     */
    public function downloadProjectsAndTasks(User $user): Response
    {
        $projectsAndTasks = collect($user->load([
            'projects' => fn(BelongsToMany $q) => $q->select([
                'projects.id',
                'projects.name',
                'projects.description',
                'projects.source',
                'projects.updated_at'
            ])->withoutGlobalScopes(),
            'tasks' => fn(BelongsToMany $q) => $q->select([
                'tasks.id',
                'tasks.project_id',
                'tasks.task_name',
                'tasks.description',
                'tasks.url',
                'tasks.priority_id',
                'tasks.status_id',
                'tasks.updated_at'
            ])->withoutGlobalScopes(),
        ]))->only(['id', 'projects', 'tasks'])->all();

        foreach ($projectsAndTasks['projects'] as $project) {
            unset($project['pivot']);
        }
        foreach ($projectsAndTasks['tasks'] as $task) {
            unset($task['pivot']);
        }

        $packed = MessagePack::pack($projectsAndTasks);

        return response()->make($packed, 200, [
            'Content-type: application/octet-stream',
            'Content-Disposition: attachment; filename=ProjectsAndTasks.cattr'
        ]);
    }

    protected const ISO8601_DATE_FORMAT = 'Y-m-d';

    /**
     * @param CalendarRequest $request
     * @return JsonResponse
     *
     * @throws Throwable
     * @api             {get} /tasks/calendar Calendar
     * @apiDescription  Get calendar report data
     *
     * @apiVersion      4.0.0
     * @apiName         Calendar
     * @apiGroup        Task
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer|Integer[]} project_id Filter by project ids
     * @apiParam {ISO8601}           start_at   Start date
     * @apiParam {ISO8601}           end_at     End date
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "project_id": 1,
     *    "start_at": "2024-10-01",
     *    "end_at": "2024-10-31"
     *  }
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": {
     *      "tasks": {
     *        "1": {
     *          "id": 1,
     *          "task_name": "Eveniet non laudantium pariatur quia.",
     *          "project_id": 1,
     *          "estimate": null,
     *          "total_spent_time": 0,
     *          "start_date": "2024-10-03",
     *          "due_date": "2024-10-03"
     *        }
     *      },
     *      "tasks_by_day": [
     *        {
     *          "date": "2024-10-03",
     *          "month": 10,
     *          "day": 3,
     *          "task_ids": [1]
     *        }
     *      ],
     *      "tasks_by_week": [
     *        {
     *          "days": [
     *            { "day": 30, "month": 9 },
     *            { "day": 1, "month": 10 },
     *            { "day": 2, "month": 10 },
     *            { "day": 3, "month": 10 },
     *            { "day": 4, "month": 10 },
     *            { "day": 5, "month": 10 },
     *            { "day": 6, "month": 10 }
     *          ],
     *          "tasks": [
     *            {
     *              "task_id": 1,
     *              "start_week_day": 3,
     *              "end_week_day": 3
     *            }
     *          ]
     *        }
     *      ]
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    public function calendar(CalendarRequest $request): JsonResponse
    {
        $requestData = $request->validated();

        $startAt = Carbon::parse($requestData['start_at'])->startOfWeek();
        $endAt = Carbon::parse($requestData['end_at'])->endOfWeek();

        /** @var Builder $query */
        $query = Task::query()
            ->select(
                'id',
                'task_name',
                'project_id',
                'status_id',
                'priority_id',
                'estimate',
                DB::raw('COALESCE(start_date, due_date) AS start_date'),
                DB::raw('COALESCE(due_date, start_date) AS due_date'),
            )
            ->with('status')
            ->with('priority')
            ->withAvg('users as efficiency', 'efficiency')
            ->withSum('workers as total_spent_time', 'duration')
            ->where(static fn(Builder $query) => $query
                ->whereNotNull('start_date')
                ->orWhereNotNull('due_date'))
            ->where(static fn(Builder $query) => $query
                ->whereBetween('start_date', [$startAt, $endAt])
                ->orWhereBetween('due_date', [$startAt, $endAt])
                ->orWhereBetween(DB::raw(DB::escape($startAt->format(static::ISO8601_DATE_FORMAT))), [DB::raw('start_date'), DB::raw('due_date')])
                ->orWhereBetween(DB::raw(DB::escape($endAt->format(static::ISO8601_DATE_FORMAT))), [DB::raw('start_date'), DB::raw('due_date')]))
            ->orderBy('start_date')
            ->orderBy('id');

        if (isset($requestData['project_id'])) {
            if (is_array($requestData['project_id'])) {
                $query->whereIn('project_id', array_values($requestData['project_id']));
            } else {
                $query->where('project_id', (int)$requestData['project_id']);
            }
        }

        /** @var \Illuminate\Support\Collection<int, Task> $tasks */
        $tasks = $query->get()->keyBy('id');

        $tasksByDay = [];
        $tasksByWeek = [];

        $period = CarbonPeriod::create($startAt, '1 day', $endAt);
        foreach ($period as $date) {
            $tasksByDay[$date->format(static::ISO8601_DATE_FORMAT)] = [
                'date' => $date->format(static::ISO8601_DATE_FORMAT),
                'day' => (int)$date->format('d'),
                'month' => (int)$date->format('m'),
                'task_ids' => [],
            ];
        }

        $period = CarbonPeriod::create($startAt, '7 days', $endAt);
        foreach ($period as $date) {
            $week = $date->format(static::ISO8601_DATE_FORMAT);
            $tasksByWeek[$week] = [
                'days' => [],
                'tasks' => [],
            ];

            $weekPeriod = CarbonPeriod::create($date, '1 day', $date->clone()->addDays(7))->excludeEndDate();
            foreach ($weekPeriod as $day) {
                $tasksByWeek[$week]['days'][] = [
                    'day' => (int)$day->format('d'),
                    'month' => (int)$day->format('m'),
                ];
            }
        }

        foreach ($tasks as $task) {
            $task->mergeCasts([
                'start_date' => 'date:' . static::ISO8601_DATE_FORMAT,
                'due_date' => 'date:' . static::ISO8601_DATE_FORMAT,
                'forecast_completion_date' => 'date:' . static::ISO8601_DATE_FORMAT,
            ]);

            $startDate = $task->start_date->greaterThan($startAt) ? $task->start_date : $startAt;
            $endDate = $task->due_date->lessThan($endAt) ? $task->due_date : $endAt;

            $period = new CarbonPeriod($startDate, '1 day', $endDate);
            foreach ($period as $date) {
                $tasksByDay[$date->format(static::ISO8601_DATE_FORMAT)]['task_ids'][] = $task->id;
            }

            $period = new CarbonPeriod($startDate->startOfWeek(), '7 days', $endDate);
            foreach ($period as $date) {
                $tasksByWeek[$date->format(static::ISO8601_DATE_FORMAT)]['tasks'][] = [
                    'task_id' => $task->id,
                    'start_week_day' => $task->start_date->greaterThan($date) ? $task->start_date->diffInDays($date) : 0,
                    'end_week_day' => $task->due_date->lessThan($date->clone()->addDays(7)) ? $task->due_date->diffInDays($date) : 6,
                ];
            }

            if ($task->start_date !== null && $task->estimate !== null && $task->efficiency !== null) {
                $task->forecast_completion_date = $task->start_date->addSeconds($task->estimate * $task->efficiency);
            } else {
                $task->forecast_completion_date = null;
            }
        }

        return responder()->success([
            'tasks' => $tasks,
            'tasks_by_day' => array_values($tasksByDay),
            'tasks_by_week' => array_values($tasksByWeek),
        ])->respond();
    }
}
