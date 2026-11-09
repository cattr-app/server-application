<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Filter;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

/**
 * @deprecated
 */
class TimeController extends Controller
{
    /**
     * @deprecated
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
     * Display a total of time
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @deprecated
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
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.time.total'), [
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

        $totalTime = $timeIntervals->sum(static fn($el) => (int) Carbon::parse($el->end_at)->diffInSeconds($el->start_at, true));

        return responder()->success([
            'time' => $totalTime,
            'start' => $timeIntervals->min('start_at'),
            'end' => $timeIntervals->max('end_at')
        ])->respond();
    }

    /**
     * Display the Tasks and theirs total time
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @deprecated
     */
    public function tasks(Request $request): JsonResponse
    {
        $validationRules = [
            'start_at' => 'date',
            'end_at' => 'date',
            'project_id' => 'exists:projects,id',
            'task_id' => 'exists:tasks,id'
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.time.total'), [
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }

        $filters = $request->all();
        $request->get('start_at') ? $filters['start_at'] = ['>=', (string)$request->get('start_at')] : false;
        $request->get('end_at') ? $filters['end_at'] = ['<=', (string)$request->get('end_at')] : false;
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : false;
        $request->get('task_id') ? $filters['task_id'] = ['in', $request->get('task_id')] : false;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        $totalTime = 0;

        $tasks = $itemsQuery
            ->with('task')
            ->get()
            ->groupBy(['task_id', 'user_id'])
            ->map(static function ($taskIntervals, $taskId) use (&$totalTime) {
                $task = [];

                foreach ($taskIntervals as $userId => $userIntervals) {
                    $taskTime = 0;

                    foreach ($userIntervals as $interval) {
                        $taskTime += (int) Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at, true);
                    }

                    $firstUserInterval = $userIntervals->first();
                    $lastUserInterval = $userIntervals->last();

                    $task = [
                        'id' => $taskId,
                        'user_id' => $userId,
                        'project_id' => $userIntervals[0]['task']['project_id'],
                        'time' => $taskTime,
                        'start' => Carbon::parse($firstUserInterval->start_at)->toISOString(),
                        'end' => Carbon::parse($lastUserInterval->end_at)->toISOString()
                    ];

                    $totalTime += $taskTime;
                }

                return $task;
            })
            ->values();

        $first = $itemsQuery->get()->first();
        $last = $itemsQuery->get()->last();

        return responder()->success([
            'tasks' => $tasks,
            'total' => [
                'time' => $totalTime,
                'start' => $first ? Carbon::parse($first->start_at)->toISOString() : null,
                'end' => $last ? Carbon::parse($last->end_at)->toISOString() : null,
            ]
        ])->respond();
    }
}
