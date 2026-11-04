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
     * List
     *
     * Get list of Tasks
     *
     * Required permission (any of): `tasks_list`, `tasks_full_access`
     *
     * @param ListTaskRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListTaskRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Edit
     *
     * Edit Task
     *
     * Required permission (any of): `tasks_edit`, `tasks_full_access`
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

            $delta = (int) $nearestChildDate->diffInDays($newDate, false);

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
            if ($request->has('users')) {
                $oldUsers = $taskBeforeChanges['_old_users'];
                $userIds = $request->input('users');
                $changes = $data->users()->sync($userIds);
                if (!empty($changes['attached']) || !empty($changes['detached']) || !empty($changes['updated'])) {
                    SaveTaskEditHistory::dispatch(
                        $data,
                        $request->user(),
                        [
                            'users' => User::withoutGlobalScopes()
                                ->whereIn('id', $userIds)
                                ->select(['id', 'full_name'])->get()->map(fn($item)=>$item->full_name)->join(', ')
                        ],
                        [
                            'users' => $oldUsers,
                        ]
                    );
                }
            }
            SaveTaskEditHistory::dispatch($data, request()->user(), null, $taskBeforeChanges);
        });

        return $this->_edit($request);
    }

    /**
     * Create
     *
     * Create Task
     *
     * Required permission (any of): `tasks_create`, `tasks_full_access`
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
     * Destroy
     *
     * Destroy Task
     *
     * Required permission (any of): `tasks_remove`, `tasks_full_access`
     */
    public function destroy(DestroyTaskRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Count
     *
     * Count Tasks
     *
     * Required permission (any of): `tasks_count`, `tasks_full_access`
     */
    public function count(ListTaskRequest $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     * Show
     *
     * Show Task
     *
     * Required permission (any of): `tasks_show`, `tasks_full_access`
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
     * Create Relation
     *
     * Creates a relation between two tasks, ensuring they belong to the same project and no cyclic dependencies exist.
     *
     * Required permission (any of): `tasks_create_relation`, `tasks_full_access`
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
            $delta = (int) $child->$childDateKey->diffInDays($parent->$parentDateKey, false);
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
     * Destroy Relation
     *
     * Removes a relation between two tasks.
     *
     * Required permission (any of): `tasks_destroy_relation`, `tasks_full_access`
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
     * Download Projects and Tasks
     *
     * Downloads all projects and tasks associated with a specific user.
     *
     * Required permission (any of): `users_download_projects_tasks`, `users_full_access`
     *
     * The response is not JSON: it is the file `ProjectsAndTasks.cattr`, a MessagePack document with the `id`
     * of the user and the `projects` and `tasks` assigned to them.
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
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename=ProjectsAndTasks.cattr'
        ]);
    }

    protected const ISO8601_DATE_FORMAT = 'Y-m-d';

    /**
     * Calendar
     *
     * Get calendar report data
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
                    'start_week_day' => $task->start_date->greaterThan($date) ? (int) $task->start_date->diffInDays($date, true) : 0,
                    'end_week_day' => $task->due_date->lessThan($date->clone()->addDays(7)) ? (int) $task->due_date->diffInDays($date, true) : 6,
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
