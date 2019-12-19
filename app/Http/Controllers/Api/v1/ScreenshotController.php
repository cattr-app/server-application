<?php

namespace App\Http\Controllers\Api\v1;

use App\Exceptions\CarnivalUnavailableException;
use App\Helpers\QueryHelper;
use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TimeInterval;
use App\Services\ImageService;
use Auth;
use Carbon\Carbon;
use Filter;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Validator;

/**
 * Class ScreenshotController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ScreenshotController extends ItemController
{

    /**
     * @var ImageService
     */
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        parent::__construct();
        $this->imageService = $imageService;
    }


    // TODO: find out why it is done this way

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Screenshot::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'time_interval_id' => 'exists:time_intervals,id|required',
            'path' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'screenshot';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'screenshots.list',
            'count' => 'screenshots.list',
            'dashboard' => 'screenshots.dashboard',
            'create' => 'screenshots.create',
            'bulkCreate' => 'screenshots.bulk-create',
            'edit' => 'screenshots.edit',
            'show' => 'screenshots.show',
            'destroy' => 'screenshots.remove',
        ];
    }

    /**
     * @apiDefine ScreenshotRelations
     *
     * @apiParam {String} [with]                      For add relation model in response
     * @apiParam {Object} [timeInterval] `QueryParam` Screenshot's relation timeInterval. All params in <a href="#api-Time_Interval-GetTimeIntervalList" >@Time_Interval</a>
     */

    /**
     * @apiDefine ScreenshotRelationsExample
     * @apiParamExample {json} Request-With-Relations-Example:
     *  {
     *      "with":                  "timeInterval,timeInterval.task",
     *      "timeInterval.tasks.id": [">", 1]
     *  }
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/screenshots/list List
     * @apiDescription Get list of Screenshots
     * @apiVersion     0.1.0
     * @apiName        GetScreenshotList
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's TimeInterval's User ID
     * @apiParam {Integer}  [project_id]       `QueryParam` Screenshot's TimeInterval's Project ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse         ScreenshotRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":               [">", 1],
     *      "time_interval_id": ["=", [1,2,3]],
     *      "user_id":          ["=", [1,2,3]],
     *      "project_id":       ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse         ScreenshotRelationsExample
     * @apiUser        UnauthorizedError
     *
     * @apiSuccess {Object[]} ScreenshotList                             Screenshots (Array of objects)
     * @apiSuccess {Object}   ScreenshotList.Screenshot                  Screenshot object
     * @apiSuccess {Integer}  ScreenshotList.Screenshot.id               Screenshot's ID
     * @apiSuccess {Integer}  ScreenshotList.Screenshot.time_interval_id Screenshot's Time Interval ID
     * @apiSuccess {String}   ScreenshotList.Screenshot.path             Screenshot's Image path URI
     * @apiSuccess {DateTime} ScreenshotList.Screenshot.created_at       Screenshot's date time of create
     * @apiSuccess {DateTime} ScreenshotList.Screenshot.updated_at       Screenshot's date time of update
     * @apiSuccess {DateTime} ScreenshotList.Screenshot.deleted_at       Screenshot's date time of delete
     * @apiSuccess {Object}   ScreenshotList.Screenshot.time_interval    Screenshot's Task
     *
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->get('user_id')) {
            $request->offsetSet('timeInterval.user_id', $request->get('user_id'));
            $request->offsetUnset('user_id');
        }

        if ($request->get('project_id')) {
            $request->offsetSet('timeInterval.task.project_id', $request->get('project_id'));
            $request->offsetUnset('project_id');
        }

        return parent::index($request);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws CarnivalUnavailableException
     * @api            {post} /api/v1/screenshots/create Create
     * @apiDescription Create Screenshot
     * @apiVersion     0.1.0
     * @apiName        CreateScreenshot
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer} time_interval_id  Screenshot's Time Interval ID
     * @apiParam {Binary}  screenshot        Screenshot file
     *
     * @apiParamExample {json} Simple-Request Example
     *  {
     *      "time_interval_id": 1,
     *      "screenshot": ```binary data```
     *  }
     *
     * @apiSuccess {Object}   Screenshot                  Screenshot object
     * @apiSuccess {Integer}  Screenshot.id               Screenshot id
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval id
     * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
     * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
     * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
     * @apiSuccess {Boolean}  Screenshot.important        Screenshot important flag
     *
     * @apiUse         DefaultCreateErrorResponse
     * @apiUse         UnauthorizedError
     *
     */
    public function create(Request $request): JsonResponse
    {
        // Request must contain screenshot
        if (!isset($request->screenshot)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'screenshot is required',
                ]),
                400);
        }

        // Get path to screenshot stored in tmp
        $screenshotPath = $request->screenshot->path();

        // Open it in order to get stream
        $screenshotStream = fopen($screenshotPath, 'r');

        // Get stream for thumb
        $thumbStream = $this->imageService->makeThumb($screenshotPath);

        try {

            // Push screen and thumb to carnival and get their public URI
            $publicScreenshotUrl = $this->imageService->pushScreenAndThumbToCarnival(
                $screenshotStream, $thumbStream, Auth::id()
            );

        } catch (ConnectException $e) {

            throw new CarnivalUnavailableException('Carnival service is unavailable');

        } catch (RequestException $e) {
            // Do nothing pls ok?
        }

        // Get interval id
        $timeIntervalID = ((int) $request->get('time_interval_id')) ?: null;

        // Pack everything we need
        $screenshotPack = [
            'time_interval_id' => $timeIntervalID,
            'path' => $publicScreenshotUrl,
            'thumbnail_path' => str_replace('.jpg', '-thumb.jpg', $publicScreenshotUrl),
        ];

        $validator = Validator::make(
            $screenshotPack,
            Filter::process(
                $this->getEventUniqueName('validation.item.create'), $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400);
        }

        $createdScreenshot = Filter::process($this->getEventUniqueName('item.create'),
            Screenshot::create($screenshotPack));

        // Respond to client
        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'success' => true,
                'screenshot' => $createdScreenshot,
            ]), 200);
    }

    public function destroy(Request $request): JsonResponse
    {

        // Get screenshot model
        $screenshotModel = $this->getItemClass();

        // Find exact screenshot to be deleted
        $screenshotToDel = $screenshotModel::where('id', $request->get('id'))->firstOrFail();

        // Save it's path
        $toDelPath = $screenshotToDel->path;

        // Get associated time interval
        $thisScreenshotTimeInterval = TimeInterval::where('id', $screenshotToDel->time_interval_id)->firstOrFail();

        // If this screenshot is last
        if ((int) $thisScreenshotTimeInterval->screenshots_count <= 1) {

            // Delete interval with it
            $thisScreenshotTimeInterval->delete();

        } else {

            // Or screenshot only otherwise
            $screenshotToDel->delete();

        }

        try {

            // Remove them from carnival
            $deletedScreenshot = $this->imageService->rmScreenAndThumbFromCarnival(Auth::id(), $toDelPath);

        } catch (ConnectException $e) {

            throw new CarnivalUnavailableException('Carnival service is unavailable');

        }

        return response()->json($deletedScreenshot);

    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/screenshots/bulk-create Bulk create
     * @apiDescription Create Screenshot
     * @apiVersion     0.1.0
     * @apiName        BulkCreateScreenshot
     * @apiGroup       Screenshot
     *
     * @apiSuccess {Object[]} messages                  Messages
     * @apiSuccess {Integer}  messages.id               Screenshot id
     * @apiSuccess {Integer}  messages.time_interval_id Screenshot Time Interval id
     * @apiSuccess {String}   messages.path             Screenshot Image path URI
     * @apiSuccess {String}   messages.created_at       Screenshot date time of create
     * @apiSuccess {String}   messages.updated_at       Screenshot date time of update
     * @apiSuccess {Boolean}  messages.important        Screenshot important flag
     *
     * @apiError (400)  {Object[]} messages         Messages
     * @apiError (400)  {String}   messages.error   Error title
     * @apiError (400)  {String}   messages.reason  Error reason
     * @apiError (400)  {String}   messages.code    Error code
     *
     * @apiUse         DefaultCreateErrorResponse
     * @apiUse         UnauthorizedError
     *
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $requestData = $request->all();
        $result = [];

        if (empty($requestData)) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.create'), [
                    [
                        'success' => false,
                        'error_type' => 'validation',
                        'message' => 'Validation error',
                        'info' => 'screenshot is required',
                    ]
                ]),
                400
            );
        }

        foreach ($requestData as $timeIntervalId => $screenshot) {
            $screenStorePath = $screenshot->store('uploads/screenshots');
            $absoluteStorePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, Storage::disk()->path($screenStorePath));

            $path = Filter::process($this->getEventUniqueName('request.item.create'), $absoluteStorePath);

            $screenshot = Image::make($path);

            $thumbnail = $screenshot->resize(280, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            $ds = DIRECTORY_SEPARATOR;

            $thumbnailPath = str_replace("uploads{$ds}screenshots", "uploads{$ds}screenshots{$ds}thumbs",
                $screenStorePath);
            Storage::put($thumbnailPath, (string) $thumbnail->encode());

            $requestData = [
                'time_interval_id' => (int) $timeIntervalId,
                'path' => $screenStorePath,
                'thumbnail_path' => $thumbnailPath,
            ];

            $validator = Validator::make(
                $requestData,
                Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
            );

            if ($validator->fails()) {
                $result[] = [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400
                ];
                continue;
            }

            $cls = $this->getItemClass();
            $item = Filter::process($this->getEventUniqueName('item.create'), $cls::create($requestData));
            $result[] = $item;
        }

        return response()->json([
            'messages' => $result,
        ]);
    }

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/screenshots/show Show
     * @apiDescription Show Screenshot
     * @apiVersion     0.1.0
     * @apiName        ShowScreenshot
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer}  id                              Screenshot id
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot Time Interval id
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {String}   [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {String}   [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse         ScreenshotRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":               1,
     *      "time_interval_id": ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         ScreenshotRelationsExample
     *
     * @apiSuccess {Object}   Screenshot                  Screenshot object
     * @apiSuccess {Integer}  Screenshot.id               Screenshot id
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval id
     * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
     * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
     * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
     * @apiSuccess {String}   Screenshot.deleted_at       Screenshot date time of delete
     * @apiSuccess {Object}   Screenshot.time_interval    Screenshot Task
     *
     * @apiUse         DefaultShowErrorResponse
     * @apiUse         UnauthorizedError
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/screenshots/edit Edit
     * @apiDescription Edit Screenshot
     * @apiVersion     0.1.0
     * @apiName        EditScreenshot
     * @apiGroup       Screenshot
     * @apiParam {Integer}  id               Screenshot id
     * @apiParam {Integer}  time_interval_id Screenshot Time Interval id
     * @apiParam {String}   path             Image path URI
     * @apiParam {DateTime} [created_at]     Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]     Last Screenshot data update DataTime
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":               1,
     *      "time_interval_id": 2,
     *      "path":             "test"
     *  }
     *
     * @apiSuccess {Object}   Screenshot                  Screenshot object
     * @apiSuccess {Integer}  Screenshot.id               Screenshot ID
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval ID
     * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
     * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
     * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
     * @apiSuccess {String}   Screenshot.deleted_at       Screenshot date time of delete
     *
     * @apiUse         DefaultEditErrorResponse
     * @apiUse         UnauthorizedError
     *
     */

    /**
     * @param  Request  $request
     *
     * @return JsonResponse
     * @api            {post} /api/v1/screenshots/remove Destroy
     * @apiUse         DefaultDestroyRequestExample
     * @apiDescription Destroy Screenshot
     * @apiVersion     0.1.0
     * @apiName        DestroyScreenshot
     * @apiGroup       Screenshot
     *
     * @apiParam {String} id Screenshot id
     *
     * @apiUse         DefaultDestroyResponse
     *
     */

    /**
     * @param  Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     * @api            {post} /api/v1/screenshots/dashboard Dashboard
     * @apiDescription Get dashboard of Screenshots
     * @apiVersion     0.1.0
     * @apiName        GetScreenshotDashboard
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's User ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse         ScreenshotRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":               1,
     *      "time_interval_id": ["=", [1,2,3]],
     *      "user_id":          ["=", [1,2,3]],
     *      "project_id":       ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse         ScreenshotRelationsExample
     * @apiUse         UnauthorizedError
     *
     * @apiSuccess {Object[]} Array                                            Array of objects
     * @apiSuccess {String}   Array.object.interval                            Time of interval
     * @apiSuccess {Object[]} Array.object.screenshots                         Screenshots of interval (Array of objects, 6 indexes)
     * @apiSuccess {Integer}  Array.object.screenshots.object.id               Screenshot ID
     * @apiSuccess {Integer}  Array.object.screenshots.object.time_interval_id Screenshot Time Interval ID
     * @apiSuccess {String}   Array.object.screenshots.object.path             Screenshot Image path URI
     * @apiSuccess {String}   Array.object.screenshots.object.created_at       Screenshot date time of create
     * @apiSuccess {String}   Array.object.screenshots.object.updated_at       Screenshot date time of update
     * @apiSuccess {String}   Array.object.screenshots.object.deleted_at       Screenshot date time of delete
     * @apiSuccess {Object}   Array.object.screenshots.object.time_interval    Screenshot Task
     *
     */
    public function dashboard(Request $request): JsonResponse
    {
        $timezone = Auth::user()->timezone;
        if (!$timezone) {
            $timezone = 'UTC';
        }

        $filters = $request->all();
        unset($filters['with']);
        is_int($request->get('user_id')) ? $filters['user_id'] = $request->get('user_id') : false;
        $compareDate = Carbon::today($timezone)->setTimezone('UTC')->toDateTimeString();
        $filters['start_at'] = ['>=', [$compareDate]];

        $query = TimeInterval::with(['screenshots', 'task', 'task.project']);
        $helper = new QueryHelper();
        $helper->apply($query, $filters ?: [], new TimeInterval());
        $baseQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.filter'),
            $query
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery->orderBy('id', 'desc')
        );

        $intervals = $itemsQuery->get();

        if (collect($intervals)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        $items = [];

        foreach ($intervals as $interval) {
            $hasInterval = false;

            $start_at = Carbon::parse($interval->start_at);
            $minutes = $start_at->minute;
            $minutes = $minutes > 9 ? (string) $minutes : '0'.$minutes;
            $hour = $start_at->hour.':00:00';

            foreach ($items as $itemkey => $item) {
                if ($item['interval'] == $hour) {
                    $hasInterval = true;
                    break;
                }
            }

            if ($hasInterval && isset($itemkey)) {
                $items[$itemkey]['intervals'][(int) $minutes{0}][] = $interval->toArray();
            } else {
                $arr = [
                    'interval' => $hour,
                    'intervals' => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => [],
                        4 => [],
                        5 => [],
                    ],
                ];

                $arr['intervals'][(int) $minutes{0}][] = $interval->toArray();
                $items[] = $arr;
            }
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items)
        );
    }

    /**
     * @param  bool  $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $user = Auth::user();
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can(Auth::user(), 'screenshots', 'full_access');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $rules = $this->getControllerRules();
        $rule = $rules[$action_method] ?? null;
        if (isset($rule)) {
            [$object, $action] = explode('.', $rule);
            // Check user default role
            if (Role::can($user, $object, $action)) {
                return $query;
            }

            $query->where(function (Builder $query) use ($user, $object, $action) {
                $user_id = $user->id;

                // Filter by project roles of the user
                $query->whereHas('timeInterval.task.project.usersRelation',
                    static function (Builder $query) use ($user_id, $object, $action) {
                        $query->where('user_id', $user_id)->whereHas('role',
                            static function (Builder $query) use ($object, $action) {
                                $query->whereHas('rules', static function (Builder $query) use ($object, $action) {
                                    $query->where([
                                        'object' => $object,
                                        'action' => $action,
                                        'allow' => true,
                                    ])->select('id');
                                })->select('id');
                            })->select('id');
                    });

                // For read and delete access include user own intervals
                $query->when($action !== 'edit', static function (Builder $query) use ($user_id) {
                    $query->orWhereHas('timeInterval', static function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->select('user_id');
                    });
                });

                $query->when($action === 'edit' && (bool) $user->manual_time,
                    static function (Builder $query) use ($user_id) {
                        $query->orWhereHas('timeInterval', static function (Builder $query) use ($user_id) {
                            $query->where('user_id', $user_id)->select('user_id');
                        });
                    });
            });
        }

        return $query;
    }
}
