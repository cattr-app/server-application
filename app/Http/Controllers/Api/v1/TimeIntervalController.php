<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Screenshot;
use App\Models\TimeInterval;
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
     */

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
}
