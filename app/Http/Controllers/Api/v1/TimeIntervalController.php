<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TimeInterval;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Filter;
use Validator;

/**
 * Class TimeIntervalController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TimeIntervalController extends ItemController
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
        return [
            'task_id'  => 'required',
            'user_id'  => 'required',
            'start_at' => 'required',
            'end_at'   => 'required',
        ];
    }

    /**
     * Create time interval
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $intervalData = [
            'task_id' => (int)$request->get('task_id'),
            'user_id' => (int)$request->get('user_id'),
            'start_at' => $request->get('start_at'),
            'end_at' => $request->get('end_at'),
            'count_mouse' => (int) $request->get('count_mouse') ?: 0,
            'count_keyboard' =>  (int) $request->get('count_keyboard') ?: 0,
        ];

        $validator = Validator::make(
            $intervalData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'validation fail',
                ]),
                400
            );
        }

        //create time interval
        $timeInterval = Filter::process($this->getEventUniqueName('item.create'), TimeInterval::create($intervalData));

        //create screenshot
        if (isset($request->screenshot)) {
            $path = Filter::process($this->getEventUniqueName('request.item.create'), $request->screenshot->store('uploads/screenshots'));

            $screenshotData = [
                'time_interval_id' => $timeInterval->id,
                'path' => $path
            ];

            $screenshot = Filter::process('item.create.screenshot', Screenshot::create($screenshotData));
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'interval' => $timeInterval,
            ]),
            200
        );
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'timeinterval';
    }

    /**
     * @api {post} /api/v1/time-intervals/list List
     * @apiDescription Get list of Time Intervals
     * @apiVersion 0.1.0
     * @apiName GetTimeIntervalList
     * @apiGroup Time Interval
     *
     * @apiParam {Integer}  [id]         `QueryParam` Time Interval ID
     * @apiParam {Integer}  [task_id]    `QueryParam` Time Interval's Task ID
     * @apiParam {Integer}  [user_id]    `QueryParam` Time Interval's User ID
     * @apiParam {String}   [start_at]   `QueryParam` Interval Start DataTime
     * @apiParam {String}   [end_at]     `QueryParam` Interval End DataTime
     * @apiParam {DateTime} [created_at] `QueryParam` Time Interval Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Time Interval data update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Time Interval was deleted (null if not)
     *
     * @apiSuccess (200) {TimeInterval[]} TimeIntervalList array of Time Interval objects
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

    /**
     * @api {post} /api/v1/time-intervals/create Create
     * @apiDescription Create Time Interval
     * @apiVersion 0.1.0
     * @apiName CreateTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/time-intervals/show Show
     * @apiDescription Show Time Interval
     * @apiVersion 0.1.0
     * @apiName ShowTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/time-intervals/edit Edit
     * @apiDescription Edit Time Interval
     * @apiVersion 0.1.0
     * @apiName EditTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/time-intervals/destroy Destroy
     * @apiDescription Destroy Time Interval
     * @apiVersion 0.1.0
     * @apiName DestroyTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'time-intervals', 'full_access');
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
