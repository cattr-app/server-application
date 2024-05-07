<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskRelationType;
use App\Exceptions\Entities\TaskRelationException;
use App\Http\Middleware\RegisterModulesEvents;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use CatEvent;
use Illuminate\Http\Response;
use MessagePack\MessagePack;
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

        CatEvent::listen(Filter::getAfterActionEventName(), static function (Task $data) use ($request) {
            $oldUsers = $data->users()->select('id', 'full_name');
            $changes = $data->users()->sync($request->get('users'));
            if (!empty($changes['attached']) || !empty($changes['detached']) || !empty($changes['updated'])) {
                SaveTaskEditHistory::dispatch(
                    $data,
                    $request->user(),
                    [
                        'users' => json_encode(User::withoutGlobalScopes()
                            ->whereIn('id', $request->get('users'))
                            ->select(['id', 'full_name'])->get()->toArray())
                    ],
                    [
                        'users' => json_encode($oldUsers->get()->toArray()),
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
        return $this->_show($request);
    }

    /**
     * @throws Throwable
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
}
