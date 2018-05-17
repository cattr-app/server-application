<?php

namespace App\Http\Controllers\Api\v1;

use Carbon\Carbon;
use Filter;
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
     * Display a total of time.
     *
     * @api {post} /api/v1/time/total
     * @apiDescription Get total of Time
     * @apiVersion 0.1.0
     * @apiName GetTimeTotal
     * @apiGroup Time
     *
     * @apiParam {DateTime} [start_at] `QueryParam` TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at] `QueryParam` TimeInterval End DataTime
     * @apiParam {Array} [tasks_id] `QueryParam` TimeInterval's Tasks ID
     * @apiParam {Integer} [project_id] `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer} [user_id] `QueryParam` TimeInterval's Task's User ID
     *
     * @apiSuccess (200) {array} array total of Time
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function total(Request $request): JsonResponse
    {
        $filters = [];
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('tasks_id') ? $filters['task_id'] = ['=', (array) $request->get('tasks_id')] : False;
        $request->get('user_id') ? $filters['user_id'] = ['=', (array) $request->get('user_id')] : False;
        $projectId = (int) $request->get('project_id')?: False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        if ($projectId) {
            $baseQuery = $baseQuery->whereHas('task', function($q) use ($projectId) {
                $q->where('project_id', '=', $projectId);
            });
        }

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
     * Display a time of project.
     *
     * @api {post} /api/v1/time/project
     * @apiDescription Get time of project
     * @apiVersion 0.1.0
     * @apiName GetTimeByProject
     * @apiGroup Time
     *
     * @apiParam {DateTime} [start_at] `QueryParam` TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at] `QueryParam` TimeInterval End DataTime
     * @apiParam {Array} [tasks_id] `QueryParam` TimeInterval's Task ID
     * @apiParam {Integer} [project_id] `QueryParam` TimeInterval's Task's Project ID {required}
     * @apiParam {Integer} [user_id] `QueryParam` TimeInterval's Task's User ID
     *
     * @apiSuccess (200) {array} array Time of Project
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function project(Request $request): JsonResponse
    {
        $projectId = (int) $request->get('project_id')?: False;
        if (!$projectId) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.get'), [
                    'error' => 'validation fail',
                    'reason' => 'project_id is required'
                ]),
                400
            );
        }

        $filters = [];
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('tasks_id') ? $filters['task_id'] = ['=', (array) $request->get('tasks_id')] : False;
        $request->get('user_id') ? $filters['user_id'] = ['=', (array) $request->get('user_id')] : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $baseQuery = $baseQuery->whereHas('task', function($q) use ($projectId) {
            $q->where('project_id', '=', $projectId);
        });

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
     * Display tasks and a time of tasks.
     *
     * @api {post} /api/v1/time/tasks
     * @apiDescription Get tasks and a time of tasks
     * @apiVersion 0.1.0
     * @apiName GetTimeByTasks
     * @apiGroup Time
     *
     * @apiParam {DateTime} [start_at] `QueryParam` TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at] `QueryParam` TimeInterval End DataTime
     * @apiParam {Array} [tasks_id] `QueryParam` TimeInterval's Task ID
     * @apiParam {Integer} [project_id] `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer} [user_id] `QueryParam` TimeInterval's Task's User ID
     *
     * @apiSuccess (200) {(DateTime)CurrentTime {}Tasks {}Total} array of Tasks objects and Total Time, Current Time
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function tasks(Request $request): JsonResponse
    {
        $filters = [];
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('tasks_id') ? $filters['task_id'] = ['=', (array) $request->get('tasks_id')] : False;
        $request->get('user_id') ? $filters['user_id'] = ['=', (array) $request->get('user_id')] : False;
        $projectId = (int) $request->get('project_id')?: False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        if ($projectId) {
            $baseQuery = $baseQuery->whereHas('task', function($q) use ($projectId) {
                $q->where('project_id', '=', $projectId);
            });
        }

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
     * Display task and time of single task.
     *
     * @api {post} /api/v1/time/task
     * @apiDescription Get task and time of single task
     * @apiVersion 0.1.0
     * @apiName GetTimeBySingleTask
     * @apiGroup Time
     *
     * @apiParam {DateTime} [start_at] `QueryParam` TimeInterval Start DataTime
     * @apiParam {DateTime} [end_at] `QueryParam` TimeInterval End DataTime
     * @apiParam {Integer} [task_id] `QueryParam` TimeInterval's Task ID {required}
     * @apiParam {Integer} [project_id] `QueryParam` TimeInterval's Task's Project ID
     * @apiParam {Integer} [user_id] `QueryParam` TimeInterval's Task's User ID
     *
     * @apiSuccess (200) {(DateTime)CurrentTime {}Task {}Total} array of Task objects and Total Time, Current Time
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function task(Request $request): JsonResponse
    {
        $filters = [];
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string) $request->get('start_at')] : False;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string) $request->get('end_at')] : False;
        $request->get('task_id') ? $filters['task_id'] = ['=', (int) $request->get('task_id')] : False;
        $request->get('user_id') ? $filters['user_id'] = ['=', (array) $request->get('user_id')] : False;
        $projectId = (int) $request->get('project_id')?: False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

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

        if ($projectId) {
            $baseQuery = $baseQuery->whereHas('task', function($q) use ($projectId) {
                $q->where('project_id', '=', $projectId);
            });
        }

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
     * @api {post} /api/v1/time/task-user
     * @apiDescription Get time of user's single task
     * @apiVersion 0.1.0
     * @apiName GetTimeBySingleTaskAndUser
     * @apiGroup Time
     *
     * @apiParam {Integer} [task_id] `QueryParam` TimeInterval's Task ID {required}
     * @apiParam {Integer} [user_id] `QueryParam` TimeInterval's Task's User ID {required}
     *
     * @apiSuccess (200) {array} array Total Time, Current Time
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskUser(Request $request): JsonResponse
    {
        $filters = [];
        $request->get('task_id') ? $filters['task_id'] = ['=', (int) $request->get('task_id')] : False;
        $request->get('user_id') ? $filters['user_id'] = ['=', (array) $request->get('user_id')] : False;

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
}