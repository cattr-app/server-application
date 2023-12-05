<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotService;
use App\Http\Requests\Interval\BulkDestroyTimeIntervalRequest;
use App\Http\Requests\Interval\BulkEditTimeIntervalRequest;
use App\Http\Requests\Interval\CreateTimeIntervalRequest;
use App\Http\Requests\Interval\DestroyTimeIntervalRequest;
use App\Http\Requests\Interval\EditTimeIntervalRequest;
use App\Http\Requests\Interval\IntervalTasksRequest;
use App\Http\Requests\Interval\IntervalTotalRequest;
use App\Http\Requests\Interval\ListIntervalRequest;
use App\Http\Requests\Interval\PutScreenshotRequest;
use App\Http\Requests\Interval\ScreenshotRequest;
use App\Http\Requests\Interval\ShowIntervalRequest;
use App\Http\Requests\Interval\TrackAppRequest;
use App\Jobs\AssignAppsToTimeInterval;
use App\Models\TrackedApplication;
use App\Models\User;
use CatEvent;
use Filter;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Settings;
use Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class IntervalController extends ItemController
{
    protected const MODEL = TimeInterval::class;

    public function __construct(protected ScreenshotService $screenshotService)
    {
    }

    /**
     * @param ListIntervalRequest $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListIntervalRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($filters) use ($request) {
            if ($request->get('project_id')) {
                $filters['task.project_id'] = $request->get('project_id');
            }

            return $filters;
        });

        return $this->_index($request);
    }

    /**
     * @api             {post} /time-intervals/create Create
     * @apiDescription  Create Time Interval
     *
     * @apiVersion      1.0.0
     * @apiName         Create
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_create
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  task_id           Task id
     * @apiParam {Integer}  user_id           User id
     * @apiParam {String}   start_at          Interval time start
     * @apiParam {String}   end_at            Interval time end
     *
     * @apiParam {Integer}  [activity_fill]   Activity rate as a percentage
     * @apiParam {Integer}  [mouse_fill]      Time spent using the mouse as a percentage
     * @apiParam {Integer}  [keyboard_fill]   Time spent using the keyboard as a percentage
     *
     * @apiParamExample {json} Request Example
     * {
     *   "task_id": 1,
     *   "user_id": 1,
     *   "start_at": "2013-04-12T16:40:00-04:00",
     *   "end_at": "2013-04-12T16:40:00-04:00"
     * }
     *
     * @apiSuccess {Object}   interval  Interval
     *
     * @apiUse          TimeIntervalObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "interval": {
     *      "id": 2251,
     *      "task_id": 1,
     *      "start_at": "2013-04-12 20:40:00",
     *      "end_at": "2013-04-12 20:40:00",
     *      "is_manual": true,
     *      "created_at": "2018-10-01 03:20:59",
     *      "updated_at": "2018-10-01 03:20:59",
     *      "activity_fill": 0,
     *      "mouse_fill": 0,
     *      "keyboard_fill": 0,
     *      "user_id": 1
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @param ShowIntervalRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     * @api             {post} /time-intervals/show Show
     * @apiDescription  Show Time Interval
     *
     * @apiVersion      1.0.0
     * @apiName         Show
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_show
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  id     Time Interval id
     *
     * @apiUse          TimeIntervalParams
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiUse          TimeIntervalObject
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "id": 1,
     *   "task_id": 1,
     *   "start_at": "2006-05-31 16:15:09",
     *   "end_at": "2006-05-31 16:20:07",
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "deleted_at": null,
     *   "activity_fill": 42,
     *   "mouse_fill": 43,
     *   "keyboard_fill": 43,
     *   "user_id": 1
     * }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */
    public function show(ShowIntervalRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /time-intervals/bulk-create Bulk Create
     * @apiDescription  Create Time Intervals
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Create
     * @apiGroup        Time Interval
     */

    /**
     * @param EditTimeIntervalRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(EditTimeIntervalRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) {
            $requestData['start_at'] = Carbon::parse($requestData['start_at'])->setTimezone('UTC')->toDateTimeString();
            $requestData['end_at'] = Carbon::parse($requestData['end_at'])->setTimezone('UTC')->toDateTimeString();
        });

        return $this->_edit($request);
    }

    /**
     * @api             {post} /time-intervals/list List
     * @apiDescription  Get list of Time Intervals
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_list
     * @apiPermission   time_intervals_full_access
     *
     * @apiUse          TimeIntervalParams
     * @apiUse          TimeIntervalObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "task_id": 1,
     *      "start_at": "2006-06-20 15:54:40",
     *      "end_at": "2006-06-20 15:59:38",
     *      "created_at": "2018-10-15 05:54:39",
     *      "updated_at": "2018-10-15 05:54:39",
     *      "deleted_at": null,
     *      "activity_fill": 42,
     *      "mouse_fill": 43,
     *      "keyboard_fill": 43,
     *      "user_id":1
     *    }
     *  ]
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @throws Exception
     * @api             {get,post} /time-intervals/count Count
     * @apiDescription  Count Time Intervals
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {String}   total    Amount of users that we have
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
    public function count(ListIntervalRequest $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     * @api             {post} /time-intervals/edit Edit
     * @apiDescription  Edit Time Interval
     *
     * @apiVersion      1.0.0
     * @apiName         Edit
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_edit
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  id           Time Interval id
     *
     * @apiUse          TimeIntervalParams
     *
     * @apiSuccess {Object}   res      TimeInterval
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id":1,
     *      "task_id":1,
     *      "start_at":"2018-10-03 10:00:00",
     *      "end_at":"2018-10-03 10:00:00",
     *      "created_at":"2018-10-15 05:50:39",
     *      "updated_at":"2018-10-15 05:50:43",
     *      "deleted_at":null,
     *      "activity_fill": 42,
     *      "mouse_fill": 43,
     *      "keyboard_fill": 43,
     *      "user_id":1
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     */

    /**
     * @api             {post} /time-intervals/bulk-edit Bulk Edit
     * @apiDescription  Multiple Edit TimeInterval to assign tasks to them
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Edit
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Object[]}  intervals          Time Intervals to edit
     * @apiParam {Integer}   intervals.id       Time Interval ID
     * @apiParam {Integer}   intervals.task_id  Task ID
     *
     * @apiParamExample {json} Request Example
     * {
     *   "intervals": [
     *     {
     *       "id": 12,
     *       "task_id": 12
     *     },
     *     {
     *       "id": 13,
     *       "task_id": 16
     *     }
     *   ]
     * }
     *
     * @apiSuccess {String}     message    Message from server
     * @apiSuccess {Integer[]}  updated    Updated intervals
     * @apiSuccess {Integer[]}  not_found  Not found intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Intervals successfully updated",
     *    "updated": [12, 123, 45],
     *  }
     *
     * @apiSuccessExample {json} Not all intervals updated Response Example
     *  HTTP/1.1 207 Multi-Status
     *  {
     *    "message": "Some intervals have not been updated",
     *    "updated": [12, 123, 45],
     *    "not_found": [154, 77, 66]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @throws Throwable
     * @api             {post} /time-intervals/remove Destroy
     * @apiDescription  Destroy Time Interval
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_remove
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  id  ID of the target interval
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
    public function destroy(DestroyTimeIntervalRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @param BulkEditTimeIntervalRequest $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function bulkEdit(BulkEditTimeIntervalRequest $request): JsonResponse
    {
        $intervalsData = collect(
            Filter::process(Filter::getRequestFilterName(), $request->validated())['intervals']
        );

        $intervals = $this->getQuery([
            'where' => [
                'id' => ['in', $intervalsData->pluck('id')->toArray()]
            ]
        ])->get()->toBase();

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$intervals, $request]);


        $intervals->each(static fn(Model $item) => Filter::process(
            Filter::getActionFilterName(),
            $item->fill(
                Arr::only(
                    $intervalsData->where('id', $item->id)->first() ?: [],
                    'task_id'
                )
            )
        ));

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$intervals, $request]);

        $intervals->each(static fn(Model $item) => $item->save());

        return responder()->success()->respond(204);
    }

    /**
     * @throws Exception
     * @api             {post} /time-intervals/bulk-remove Bulk Destroy
     * @apiDescription  Multiple Destroy TimeInterval
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Destroy
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_bulk_remove
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer[]}  intervals  Intervals ID to delete
     *
     * @apiParamExample {json} Request Example
     * {
     *   "intervals": [ 1, 2, 3 ]
     * }
     *
     * @apiSuccess {String}     message    Message from server
     * @apiSuccess {Integer[]}  removed    Removed intervals
     * @apiSuccess {Integer[]}  not_found  Not found intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Intervals successfully removed",
     *    "removed": [12, 123, 45],
     *  }
     *
     * @apiSuccessExample {json} Not all intervals removed Response Example
     *  HTTP/1.1 207 Multi-Status
     *  {
     *    "message": "Some intervals have not been removed",
     *    "removed": [12, 123, 45],
     *    "not_found": [154, 77, 66]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     *
     */
    public function bulkDestroy(BulkDestroyTimeIntervalRequest $request): JsonResponse
    {
        $intervalIds = Filter::process(Filter::getRequestFilterName(), $request->validated())['intervals'];

        $itemsQuery = $this->getQuery(['where' => ['id' => ['in', $intervalIds]]]);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$intervalIds, $request]);

        $itemsQuery->eachById(static fn($item) => Filter::process(Filter::getActionFilterName(), $item)->delete());

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$intervalIds, $request]);

        return responder()->success()->respond(204);
    }

    public function trackApp(TrackAppRequest $request): JsonResponse
    {
        return responder()->success(
            TrackedApplication::create(
                array_merge(
                    $request->validated(), ['user_id' => auth()->user()->id]
                )
            )
        )->respond();
    }

    /**
     * @throws Throwable
     */
    public function create(CreateTimeIntervalRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $requestData) {
                $timezone = Settings::scope('core')->get('timezone', 'UTC');

                $requestData['start_at'] = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
                $requestData['end_at'] = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

                return $requestData;
            }
        );

        $screenshotService = $this->screenshotService;

        if ($request->hasFile('screenshot') && optional($request->file('screenshot'))->isValid()) {
            $path = $request->file('screenshot')->store('tmp');

            CatEvent::listen(
                Filter::getAfterActionEventName(),
                static function ($data) use ($path, $screenshotService) {
                    $screenshotService->saveScreenshot(Storage::path($path), $data);
                    dispatch(static fn() => Storage::delete($path))->delay(now()->addMinute());
                }
            );
        }

        CatEvent::listen(
            Filter::getAfterActionEventName(),
            static function ($data) {
                if (User::find($data['user_id'])->web_and_app_monitoring) {
                    AssignAppsToTimeInterval::dispatch($data);
                }
            }
        );

        return $this->_create($request);
    }

    public function showScreenshot(ScreenshotRequest $request, TimeInterval $interval): BinaryFileResponse
    {
        $path = $this->screenshotService->getScreenshotPath($interval);
        if (!Storage::exists($path)) {
            abort(404);
        }

        $fullPath = Storage::path($path);

        return response()->file($fullPath);
    }

    public function showThumbnail(ScreenshotRequest $request, TimeInterval $interval): BinaryFileResponse
    {
        $path = $this->screenshotService->getThumbPath($interval);
        if (!Storage::exists($path)) {
            abort(404);
        }

        $fullPath = Storage::path($path);

        return response()->file($fullPath);
    }

    public function putScreenshot(PutScreenshotRequest $request, TimeInterval $interval): JsonResponse
    {
        $data = $request->validated();

        abort_if(
            Storage::exists($this->screenshotService->getScreenshotPath($interval)),
            409,
            __('Screenshot for requested interval already exists')
        );

        $this->screenshotService->saveScreenshot($data['screenshot'], $interval);

        return responder()->success()->respond(204);
    }

    /**
     * Display a total of time
     * @param IntervalTotalRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function total(IntervalTotalRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        $start_at = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
        $end_at = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

        $filters = [
            'where' => [
                'start_at' => ['>=', $start_at],
                'end_at' => ['<=', $end_at],
                'user_id' => ['=', $requestData['user_id']],
            ],
        ];

        $itemsQuery = $this->getQuery($filters);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $filters);

        $timeIntervals = Filter::process(Filter::getActionFilterName(), $itemsQuery->get());

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$timeIntervals, $filters]);

        $totalTime = $timeIntervals->sum(static fn($el) => Carbon::parse($el->end_at)->diffInSeconds($el->start_at));

        return responder()->success([
            'time' => $totalTime,
            'start' => $timeIntervals->min('start_at'),
            'end' => $timeIntervals->max('end_at'),
        ])->respond();
    }

    /**
     * @throws Exception
     */
    public function tasks(IntervalTasksRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $timezone = Settings::scope('core')->get('timezone', 'UTC');

        $filters = [];

        if (isset($requestData['start_at'])) {
            $filters['start_at'] = ['>=', Carbon::parse($requestData['start_at'])->setTimezone($timezone)];
        }

        if (isset($requestData['end_at'])) {
            $filters['end_at'] = ['<=', Carbon::parse($requestData['end_at'])->setTimezone($timezone)];
        }

        if (isset($requestData['project_id'])) {
            $filters['task.project_id'] = $requestData['project_id'];
        }

        if (isset($requestData['task_id'])) {
            $filters['task_id'] = ['in', $requestData['task_id']];
        }

        if (isset($requestData['user_id'])) {
            $filters['user_id'] = $requestData['user_id'];
        }

        $itemsQuery = $this->getQuery($filters ? ['where' => $filters] : []);

        $tasks = $itemsQuery
            ->with('task')
            ->get()
            ->groupBy(['task_id', 'user_id'])
            ->map(static function ($taskIntervals, $taskId) use (&$totalTime) {
                $task = [];

                foreach ($taskIntervals as $userId => $userIntervals) {
                    $taskTime = 0;
                    foreach ($userIntervals as $interval) {
                        $taskTime += Carbon::parse($interval->end_at)->diffInSeconds($interval->start_at);
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
