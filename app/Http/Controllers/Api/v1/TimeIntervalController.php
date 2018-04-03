<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\TimeInterval;

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
            'start_at'        => 'required',
            'end_at' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'timeinterval';
    }

    /**
     * @api {post} /api/v1/timeintervals/list List
     * @apiDescription Get list of Time Intervals
     * @apiVersion 0.1.0
     * @apiName GetTimeIntervalList
     * @apiGroup Time Interval
     *
     * @apiParam {Integer} [id] `QueryParam` Time Interval ID
     * @apiParam {Integer} [task_id] `QueryParam` Time Interval's Task ID
     * @apiParam {String} [start_at] `QueryParam` Interval Start DataTime
     * @apiParam {String} [end_at] `QueryParam` Interval End DataTime
     * @apiParam {DateTime} [created_at] `QueryParam` Time Interval Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Time Interval data update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Time Interval was deleted (null if not)
     *
     * @apiSuccess (200) {TimeInterval[]} TimeIntervalList array of Time Interval objects
     */

    /**
     * @api {post} /api/v1/timeintervals/create Create
     * @apiDescription Create Time Interval
     * @apiVersion 0.1.0
     * @apiName CreateTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/timeintervals/show Show
     * @apiDescription Show Time Interval
     * @apiVersion 0.1.0
     * @apiName ShowTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/timeintervals/edit Edit
     * @apiDescription Edit Time Interval
     * @apiVersion 0.1.0
     * @apiName EditTimeInterval
     * @apiGroup Time Interval
     */

    /**
     * @api {post} /api/v1/timeintervals/destroy Destroy
     * @apiDescription Destroy Time Interval
     * @apiVersion 0.1.0
     * @apiName DestroyTimeInterval
     * @apiGroup Time Interval
     */
}
