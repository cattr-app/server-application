<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotService;
use App\Enums\ScreenshotsState;
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
use App\Http\Requests\Interval\UploadOfflineIntervalsRequest;
use App\Http\Requests\Interval\UploadOfflineScreenshotsRequest;
use App\Jobs\AssignAppsToTimeInterval;
use App\Models\Task;
use App\Models\TrackedApplication;
use App\Models\User;
use CatEvent;
use Filter;
use App\Models\TimeInterval;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use MessagePack\MessagePack;
use phpseclib3\Crypt\RSA;
use Settings;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Storage;
use Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use Validator;
use ZipArchive;

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

    /**
     * @api             {post} /time-intervals/list List
     * @apiDescription   Get list of Time Intervals
     *
     * @apiVersion      4.0.0
     * @apiName         List
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_list
     * @apiPermission   time_intervals_full_access
     * @apiUse          TimeIntervalObject
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
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
     * @param ShowIntervalRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     * @api             {post} /time-intervals/show Show
     * @apiDescription  Show Time Interval
     *
     * @apiVersion      4.0.0
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
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiUse          TimeIntervalObject
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
     * @throws Throwable
     * @api             {post} /time-intervals/remove Destroy
     * @apiDescription  Destroy Time Interval
     *
     * @apiVersion      4.0.0
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
     *  HTTP/1.1 204 No Content
     *  {
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
    /**
     * @api             {post} /time-intervals/bulk-edit Bulk Edit
     * @apiDescription  Multiple Edit TimeInterval to assign tasks to them
     *
     * @apiVersion      4.0.0
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
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 No Content
     *  {
     *  }
     *
     * @apiSuccess {String}     message    Message from server
     * @apiSuccess {Integer[]}  updated    Updated intervals
     * @apiSuccess {Integer[]}  not_found  Not found intervals
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

        $intervals->each(static function (Model $item) {
            $item->save();
        });

        return responder()->success()->respond(204);
    }

    /**
     * @throws Exception
     * @api             {post} /time-intervals/bulk-remove Bulk Destroy
     * @apiDescription  Multiple Destroy TimeInterval
     *
     * @apiVersion      4.0.0
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
     *   "intervals": [1]
     * }
     *
     * @apiSuccess {String}     message    Message from server
     * @apiSuccess {Integer[]}  removed    Removed intervals
     * @apiSuccess {Integer[]}  not_found  Not found intervals
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 No Content
     *  {
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

        $itemsQuery->eachById(static function ($item) {
            Filter::process(Filter::getActionFilterName(), $item)->delete();
        });

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$intervalIds, $request]);

        return responder()->success()->respond(204);
    }
    /**
     * @api             {put} /time-intervals/app Adds the current user’s ID
     * @apiDescription  Adds the current user’s ID
     *
     * @apiVersion      4.0.0
     * @apiName         Adds the current user’s ID
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer[]}  intervals  Intervals ID to delete
     *
     * @apiParamExample {json} Request Example
     * {
     *   "executable":  "1"
     * }
     *
     * @apiSuccess {String}     executable  Indicates if the interval is executable.
     * @apiSuccess {Integer[]}  user_id     ID of the user
     * @apiSuccess {Integer[]}  updated_at  Last update timestamp
     * @apiSuccess {Integer[]}  created_at  Creation timestamp
     * @apiSuccess {Integer[]}  id          ID of the time interval
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200
     *  {
     *  "executable": "1",
     *   "user_id": 1,
     *   "updated_at": "2024-08-17T12:32:11.000000Z",
     *   "created_at": "2024-08-17T12:32:11.000000Z",
     *   "id": 1
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     *
     */
    public function trackApp(TrackAppRequest $request): JsonResponse
    {
        return responder()->success(
            TrackedApplication::create(
                array_merge(
                    $request->validated(),
                    ['user_id' => auth()->user()->id]
                )
            )
        )->respond();
    }

    /**
     * @throws Throwable
     * @api             {post} /time-intervals/create Create
     * @apiDescription  Create Time Interval
     *
     * @apiVersion      4.0.0
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
     * @apiParam {String}   timezone          Interval time end
     * @apiParam {Boolean}  is_manual         Indicates whether the time was logged manually (true) or automatically
     * @apiParam {File}     screenshot        Image
     *
     * @apiParamExample {json} Request Example
     * {
     *   "task_id": 59,
     *   "user_id": 7,
     *   "start_at": "2024-08-16T18:00:00.000Z",
     *   "end_at": "2024-08-16T18:10:00.000Z",
     *   "timezone": "Asia/Omsk",
     *   "is_manual": true
     * }
     *
     * @apiSuccess {Object}   interval  Interval
     *
     * @apiUse          TimeIntervalObject
     *
     * @apiSuccess {Integer}  id             ID of the time interval
     * @apiSuccess {Integer}  task_id        ID of the task
     * @apiSuccess {Integer}  user_id        ID of the user
     * @apiSuccess {String}   start_at       Start timestamp of the time interval
     * @apiSuccess {String}   end_at         End timestamp of the time interval
     * @apiSuccess {String}   created_at     Creation timestamp
     * @apiSuccess {String}   updated_at     Last update timestamp
     * @apiSuccess {Boolean}  is_manual      Indicates whether the time was logged manually (true) or automatically
     * @apiSuccess {Integer}  has_screenshot Indicates if there is a screenshot for this interval
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *   "id": 22578,
     *   "task_id": 59,
     *   "user_id": 7,
     *   "start_at": "2024-08-16T18:00:00.000000Z",
     *   "end_at": "2024-08-16T18:10:00.000000Z",
     *   "created_at": "2024-08-16T20:07:14.000000Z",
     *   "updated_at": "2024-08-16T20:07:14.000000Z",
     *   "is_manual": true,
     *   "has_screenshot": true
     * }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
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
                static function (TimeInterval $interval) use ($path, $screenshotService) {
                    $projScreenshotsState = $interval->task->project->screenshots_state;
                    $mustCapture = $projScreenshotsState === ScreenshotsState::REQUIRED;
                    $optionalCapture = $projScreenshotsState === ScreenshotsState::OPTIONAL;
                    if ($mustCapture
                        || ($optionalCapture && $interval->user->screenshots_state === ScreenshotsState::REQUIRED)
                    ) {
                        $screenshotService->saveScreenshot(Storage::path($path), $interval);
                        dispatch(static fn() => Storage::delete($path))->delay(now()->addMinute());
                    }
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

        $projScreenshotsState = $interval->task->project->screenshots_state;
        $mustCapture = $projScreenshotsState === ScreenshotsState::REQUIRED;
        $optionalCapture = $projScreenshotsState === ScreenshotsState::OPTIONAL;
        if ($mustCapture
            || ($optionalCapture && $interval->user->screenshots_state === ScreenshotsState::REQUIRED)
        ) {
            $this->screenshotService->saveScreenshot($data['screenshot'], $interval);
        } else {
            abort(
                409,
                __('Screenshots disabled for interval\'s project')
            );
        }

        return responder()->success()->respond(204);
    }

    public function uploadOfflineIntervals(UploadOfflineIntervalsRequest $request): JsonResponse
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->validated()['file'];

        $zip = new ZipArchive;
        $zipOpenResult = $zip->open($file->path());
        abort_if(
            $zipOpenResult === false || (is_int($zipOpenResult) && $zipOpenResult > 0),
            400,
            __('Cannot open file.' . is_int($zipOpenResult) ? " ZipArchive error code: $zipOpenResult" : ""),
        );

        $temporaryDirectory = (new TemporaryDirectory())->deleteWhenDestroyed()->force()->create();
        $zip->extractTo($temporaryDirectory->path());
        $zip->close();

        $privateKey = RSA::load(Settings::scope('core.offline-sync')->get('private_key'));

        $intervalsContent = file_get_contents($temporaryDirectory->path('Intervals'));
        $digestContent = file_get_contents($temporaryDirectory->path('EncryptedDigest'));

        abort_if(
            $intervalsContent === false || $digestContent === false,
            400,
            __('Unable to read content of Intervals or its EncryptedDigest')
        );

        $digest = $privateKey->withPadding(RSA::ENCRYPTION_OAEP)->withHash('sha256')->decrypt($digestContent);
        $actualDigest = openssl_digest($intervalsContent, 'sha256');

        abort_if(
            $digest === false || $actualDigest === false || $digest !== $actualDigest,
            400,
            __('Unable to verify Intervals digest')
        );

        $intervals = MessagePack::unpack($intervalsContent);

        $timezone = Settings::scope('core')->get('timezone', 'UTC');
        $validatorClass = new CreateTimeIntervalRequest();
        $creationResult = [];

        abort_if(
            count($intervals) === 0,
            400,
            __('File contains 0 intervals')
        );

        $user = User::whereId($intervals[0]['user_id'])->first(['id', 'email', 'full_name', 'screenshots_state']);

        abort_if(
            $user === null,
            400,
            __('User not found')
        );

        $canCreate = fn($interval) => $request->user()->can(
            'create',
            [
                TimeInterval::class,
                $interval['user_id'],
                $interval['task_id'],
                false,
            ],
        );

        $tasksScreenshotsState = Task::with('project:id,screenshots_state')
            ->whereIn('id', collect($intervals)->pluck('task_id'))
            ->select(['id', 'project_id'])
            ->get()
            ->mapWithKeys(fn($item)=>[$item->id => $item->project->screenshots_state])
            ->toArray();
        $globalScreenshotsState = ScreenshotsState::withGlobalOverrides(null) ?? ScreenshotsState::OPTIONAL;

        foreach ($intervals as $interval) {
            $interval['user'] = $user;

            if ($canCreate($interval) === false) {
                $creationResult[] = [
                    'interval' => $interval,
                    'message' => __('validation.offline-sync.cannot_create_interval'),
                    'success' => false
                ];
                continue;
            }

            $screenshotIdValidationRule = $interval['has_screenshot'] ? ['screenshot_id' => 'required|uuid'] : [];

            if ($interval['has_screenshot']) {
                $mustNotCapture = $globalScreenshotsState === ScreenshotsState::FORBIDDEN;
                $optionalCapture = $globalScreenshotsState === ScreenshotsState::OPTIONAL
                    && isset($tasksScreenshotsState[$interval['task_id']]);

                if ($optionalCapture && $tasksScreenshotsState[$interval['task_id']] === ScreenshotsState::FORBIDDEN) {
                    $mustNotCapture = true;
                } elseif ($optionalCapture
                    && $tasksScreenshotsState[$interval['task_id']] === ScreenshotsState::OPTIONAL) {
                    $mustNotCapture = $user->screenshots_state === ScreenshotsState::FORBIDDEN;
                }
                if ($mustNotCapture) {
                    $screenshotIdValidationRule = [];
                }
            }

            $intervalValidator = Validator::make(
                $interval,
                array_merge(
                    $validatorClass->getRules($interval['user_id'], $interval['start_at'], $interval['end_at']),
                    $screenshotIdValidationRule
                )
            );

            if ($intervalValidator->fails()) {
                $creationResult[] = [
                    'interval' => $interval,
                    'message' => $intervalValidator->errors(),
                    'success' => false
                ];
                continue;
            }

            $requestData = $intervalValidator->validated();
            $requestData['start_at'] = Carbon::parse($requestData['start_at'])->setTimezone($timezone);
            $requestData['end_at'] = Carbon::parse($requestData['end_at'])->setTimezone($timezone);

            TimeInterval::create($requestData);
            $creationResult[] = [
                'interval' => $interval,
                'message' => __('validation.offline-sync.time_interval_added'),
                'success' => true
            ];
        }


        return responder()->success($creationResult)->respond();
    }

    public function uploadOfflineScreenshots(UploadOfflineScreenshotsRequest $request): JsonResponse
    {
        /**
         * @var UploadedFile $file
         */
        $file = $request->validated()['file'];

        $zip = new ZipArchive;
        $zipOpenResult = $zip->open($file->path());
        abort_if(
            $zipOpenResult === false || (is_int($zipOpenResult) && $zipOpenResult > 0),
            400,
            __('Cannot open file.' . is_int($zipOpenResult) ? " ZipArchive error code: $zipOpenResult" : ""),
        );

        $temporaryDirectory = (new TemporaryDirectory())
            ->location(Storage::disk('local')->path('tmp'))->force()->create();
        $zip->extractTo($temporaryDirectory->path());
        $zip->close();

        $dirPath = Str::of($temporaryDirectory->path())->match('/tmp.+/');
        dispatch(static fn() => $temporaryDirectory->delete())->delay(now()->addHour());


        $allScreenshots = Storage::disk('local')->files($dirPath);

        $creationResult = [];

        $screenshotService = $this->screenshotService;
        foreach ($allScreenshots as $screenshotPath) {
            $pathArr = Str::of($screenshotPath)->match('/\d_.+/')->split('/_/');
            abort_if(
                count($pathArr) !== 2 || (count($pathArr) === 2 && !Str::isUuid($pathArr[1])),
                400,
                __('Wrong screenshot file name')
            );

            [$userId, $screenshotId] = $pathArr;

            $interval = TimeInterval::where('user_id', $userId)->where('screenshot_id', $screenshotId)->first();
            if ($interval === null) {
                $creationResult[] = [
                    'interval' => $interval,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.cannot_find_interval'),
                    'success' => false
                ];
                continue;
            }
            try {
                dispatch(static function () use ($screenshotService, $interval, $screenshotPath) {
                    $screenshotService->saveScreenshot(Storage::path($screenshotPath), $interval);
                    $interval->screenshot_id = null;
                    $interval->save();
                });
                $creationResult[] = [
                    'interval' => $interval,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.screenshot_attached'),
                    'success' => true
                ];
            } catch (\Exception $e) {
                $creationResult[] = [
                    'interval' => $interval,
                    'user_id' => $userId,
                    'screenshot_id' => $screenshotId,
                    'message' => __('validation.offline-sync.screenshot_not_attached'),
                    'success' => false
                ];
                \Log::error($e);
            }
        }

        return responder()->success($creationResult)->respond();
    }

    /**
     * Display a total of time
     * @param IntervalTotalRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    /**
     * @api             {any} /time/total Calculate total time interval
     * @apiDescription  Calculate the total time spent within the specified start and end intervals for a given user.
     *
     * @apiVersion      4.0.0
     * @apiName         CalculateTotalTime
     * @apiGroup        Time Interval
     *
     * @apiUse          AuthHeader
     * @apiUse ParamTimeInterval
     *
     * @apiSuccess {Integer}  time  Total time in seconds calculated between the intervals.
     * @apiSuccess {String}   start The earliest start datetime.
     * @apiSuccess {String}   end   The latest end datetime.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "time": 1200,
     *      "start": "2024-08-16 18:00:00",
     *      "end": "2024-08-17 11:10:00"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     *
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
    /**
    * @api             {any} /time/tasks Calculate total time interval
    * @apiDescription  Calculate the total time spent within the specified start and end intervals for a given user.
    *
    * @apiVersion      4.0.0
    * @apiName         CalculateTotalTime
    * @apiGroup        Time Interval
    *
    * @apiUse          AuthHeader
    *
    * @apiUse ParamTimeInterval
    *
    * @apiSuccess {Integer}   tasks.id           The ID of the task.
    * @apiSuccess {Integer}   tasks.user_id      The ID of the user.
    * @apiSuccess {Integer}   tasks.project_id   The latest end datetime.
    * @apiSuccess {Integer}   tasks.time         Total time in seconds calculated between the intervals.
    * @apiSuccess {String}    tasks.start        The earliest start datetime.
    * @apiSuccess {String}    tasks.end          The latest end datetime.
    * @apiSuccess {Integer}   total.time         Total time in seconds calculated between the intervals.
    * @apiSuccess {String}    total.start        The earliest start datetime.
    * @apiSuccess {String}    total.end          The latest end datetime.
    *
    * @apiSuccessExample {json} Response Example
    *  HTTP/1.1 200 OK
    *  {
    *   "tasks": [
    *        {
    *            "id": 59,
    *            "user_id": 7,
    *            "project_id": 2,
    *            "time": 1200,
    *            "start": "2024-08-16T18:00:00.000000Z",
    *            "end": "2024-08-17T11:10:00.000000Z"
    *        }
    *    ],
    *    "total": {
    *        "time": 1200,
    *        "start": "2024-08-16T18:00:00.000000Z",
    *        "end": "2024-08-17T11:10:00.000000Z"
    *    }
    * }
    * @apiUse          400Error
    * @apiUse          ValidationError
    * @apiUse          ForbiddenError
    * @apiUse          UnauthorizedError
    *
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
