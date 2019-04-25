<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TimeInterval;
use App\Rules\BetweenDate;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Filter;
use Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

/**
 * Class TimeIntervalController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TimeIntervalController extends ItemController
{
    /**
     * @apiDefine WrongDateTimeFormatStartEndAt
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiErrorExample {json} DateTime validation fail
     * {
     *   "error": "validation fail",
     *     "reason": {
     *     "start_at": [
     *       "The start at does not match the format Y-m-d\\TH:i:sP."
     *     ],
     *     "end_at": [
     *       "The end at does not match the format Y-m-d\\TH:i:sP."
     *     ]
     *   }
     * }
     */

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return TimeInterval::class;
    }


    /**
     * @param int $user_id
     * @param string $start_at
     * @return array
     */
    public function getValidationRules(int $user_id = 0, string $start_at = ''): array
    {
        $user = User::find($user_id);


        $end_at_rules = [
            'date_format:'.DATE_ATOM,
            'required',
        ];

        if ($user) {
            $timeOffset = $user->screenshots_interval /* min */ * 60 /* sec */;
            $beforeTimestamp = strtotime($start_at) + $timeOffset;
            $beforeDate = date(DATE_ATOM, $beforeTimestamp);

            $end_at_rules[] = new BetweenDate($start_at, $beforeDate);
        }

        return [
            'task_id'  => 'required',
            'user_id'  => 'required',
            'start_at' => 'date_format:'.DATE_ATOM.'|required',
            'end_at'   => $end_at_rules,
        ];
    }

    /**
     * @api {post} /api/v1/time-intervals/create Create
     * @apiDescription Create Time Interval
     * @apiVersion 0.1.0
     * @apiName CreateTimeInterval
     * @apiGroup Time Interval
     *
     * @apiUse UnauthorizedError
     *
     * @apiRequestExample {json} Request Example
     * {
     *   "task_id": 1,
     *   "user_id": 1,
     *   "start_at": "2013-04-12T16:40:00-04:00",
     *   "end_at": "2013-04-12T16:40:00-04:00"
     * }
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "interval": {
     *     "id": 2251,
     *     "task_id": 1,
     *     "start_at": "2013-04-12 20:40:00",
     *     "end_at": "2013-04-12 20:40:00",
     *     "created_at": "2018-10-01 03:20:59",
     *     "updated_at": "2018-10-01 03:20:59",
     *     "count_mouse": 0,
     *     "count_keyboard": 0,
     *     "user_id": 1
     *   }
     * }
     *
     * @apiParam {Integer}  task_id   Task id
     * @apiParam {Integer}  user_id   User id
     * @apiParam {String}   start_at  Interval time start
     * @apiParam {String}   end_at    Interval time end
     *
     * @apiParam {Integer}  [count_mouse]     Mouse events count
     * @apiParam {Integer}  [count_keyboard]  Keyboard events count
     *
     * @apiUse WrongDateTimeFormatStartEndAt
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
            Filter::process(
                $this->getEventUniqueName('validation.item.create'),
                $this->getValidationRules(
                    $intervalData['user_id'] ?? 0,
                    $intervalData['start_at'] ?? ''
                ))
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        //create time interval
        $intervalData['start_at'] = (new Carbon($intervalData['start_at']))->setTimezone('UTC')->toDateTimeString();
        $intervalData['end_at'] = (new Carbon($intervalData['end_at']))->setTimezone('UTC')->toDateTimeString();

        // If interval is already exists, do not create duplicate.
        $existing = TimeInterval::where([
            ['user_id', '=', $intervalData['user_id']],
            ['start_at', '=', $intervalData['start_at']],
            ['end_at', '=', $intervalData['end_at']],
        ])->first();
        if ($existing) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                    'interval' => $existing,
                ]),
                200
            );
        }

        $timeInterval = Filter::process($this->getEventUniqueName('item.create'), TimeInterval::create($intervalData));

        //create screenshot
        if (isset($request->screenshot)) {
            $path = Filter::process($this->getEventUniqueName('request.item.create'), $request->screenshot->store('uploads/screenshots'));
            $screenshot = Image::make($path);
            $thumbnail = $screenshot->resize(280, null, function ($constraint) {
                $constraint->aspectRatio();
            });
            $thumbnailPath = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);
            Storage::put($thumbnailPath, (string) $thumbnail->encode());

            $screenshotData = [
                'time_interval_id' => $timeInterval->id,
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
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
     * @apiParam {Integer}  [id]         `QueryParam` Time Interval id
     * @apiParam {Integer}  [task_id]    `QueryParam` Time Interval Task id
     * @apiParam {Integer}  [user_id]    `QueryParam` Time Interval User id
     * @apiParam {String}   [start_at]   `QueryParam` Interval Start DataTime
     * @apiParam {String}   [end_at]     `QueryParam` Interval End DataTime
     * @apiParam {String}   [created_at] `QueryParam` Time Interval Creation DateTime
     * @apiParam {String}   [updated_at] `QueryParam` Last Time Interval data update DataTime
     * @apiParam {String}   [deleted_at] `QueryParam` When Time Interval was deleted (null if not)
     *
     * @apiSuccess (200) {Object[]} TimeIntervalList Time Intervals
     *
     * @apiSuccessExample {json} Answer Example:
     * {
     *      {
     *          "id":1,
     *          "task_id":1,
     *          "start_at":"2006-06-20 15:54:40",
     *          "end_at":"2006-06-20 15:59:38",
     *          "created_at":"2018-10-15 05:54:39",
     *          "updated_at":"2018-10-15 05:54:39",
     *          "deleted_at":null,
     *          "count_mouse":42,
     *          "count_keyboard":43,
     *          "user_id":1
     *      },
     *      ...
     * }
     *
     * @apiUse UnauthorizedError
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
     * @api {post} /api/v1/time-intervals/show Show
     * @apiDescription Show Time Interval
     * @apiVersion 0.1.0
     * @apiName ShowTimeInterval
     * @apiGroup Time Interval
     *
     * @apiParam {Integer}  id     Time Interval id
     *
     * @apiRequestExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Object}  object TimeInterval
     * @apiSuccess {Integer} object.id
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "id": 1,
     *   "task_id": 1,
     *   "start_at": "2006-05-31 16:15:09",
     *   "end_at": "2006-05-31 16:20:07",
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "deleted_at": null,
     *   "count_mouse": 88,
     *   "count_keyboard": 127,
     *   "user_id": 1
     * }
     *
     * @apiUse UnauthorizedError
     */

    /**
     * @api {post} /api/v1/time-intervals/edit Edit
     * @apiDescription Edit Time Interval
     * @apiVersion 0.1.0
     * @apiName EditTimeInterval
     * @apiGroup Time Interval
     *
     * @apiParam {Integer}  id           Time Interval id
     * @apiParam {Integer}  [user_id]    Time Interval User id
     * @apiParam {String}   [start_at]   Interval Start DataTime
     * @apiParam {String}   [end_at]     Interval End DataTime
     * @apiParam {String}   [created_at] Time Interval Creation DateTime
     * @apiParam {String}   [updated_at] Last Time Interval data update DataTime
     * @apiParam {String}   [deleted_at] When Time Interval was deleted (null if not)
     *
     * @apiSuccess {Object} res                 TimeInterval
     * @apiSuccess {Object} res.id              TimeInterval id
     * @apiSuccess {Object} res.user_id.        User id
     * @apiSuccess {Object} res.start_at        Start datetime
     * @apiSuccess {Object} res.end_at          End datetime
     * @apiSuccess {Object} res.created_at      TimeInterval
     * @apiSuccess {Object} res.deleted_at      TimeInterval
     *
     *
     * @apiSuccessExample {json} Answer example
     * {
     * "res":
     *   {
     *     "id":1,
     *     "task_id":1,
     *     "start_at":"2018-10-03 10:00:00",
     *     "end_at":"2018-10-03 10:00:00",
     *     "created_at":"2018-10-15 05:50:39",
     *     "updated_at":"2018-10-15 05:50:43",
     *     "deleted_at":null,
     *     "count_mouse":42,
     *     "count_keyboard":43,
     *     "user_id":1
     *   }
     * }
     *
     *
     * @apiUse UnauthorizedError
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $request->all()
        );

        $validationRules = $this->getValidationRules($requestData['user_id'], $requestData['start_at']);
        $validationRules['id'] = ['required'];

        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $validationRules
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        //create time interval
        $requestData['start_at'] = (new Carbon($requestData['start_at']))->setTimezone('UTC')->toDateTimeString();
        $requestData['end_at'] = (new Carbon($requestData['end_at']))->setTimezone('UTC')->toDateTimeString();

        if (!is_int($request->get('id'))) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Invalid id',
                    'reason' => 'Id is not integer',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery()
            )
        );

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = collect($itemsQuery->get())->first(function ($val, $key) use ($request) {
            return $val['id'] === $request->get('id');
        });

        if (!$item) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Model fetch fail',
                    'reason' => 'Model not found',
                ]),
                400
            );
        }

        $item->fill($this->filterRequestData($requestData));
        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api {delete, post} /api/v1/time-intervals/remove Destroy
     * @apiDescription Destroy Time Interval
     * @apiVersion 0.1.0
     * @apiName DestroyTimeInterval
     * @apiGroup Time Interval
     *
     * @apiParam {Integer}   id Time interval id
     *
     * @apiSuccess {String} message Message
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "message":"Item has been removed"
     * }
     *
     * @apiUse UnauthorizedError
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
