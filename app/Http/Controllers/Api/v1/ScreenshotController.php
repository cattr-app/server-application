<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\QueryHelper;
use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TimeInterval;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Validator;

/**
 * Class ScreenshotController
*/
class ScreenshotController extends ItemController
{
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
     * @api             {get,post} /v1/screenshots/list List
     * @apiDescription  Get list of Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_list
     * @apiPermission   screenshots_full_access
     *
     * @apiUse          UserParams
     *
     * @apiParamExample {json} Request Example
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
     * @apiUse          UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "time_interval_id": 1,
     *      "path": "uploads\/screenshots\/1_1_1.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    },
     *    {
     *      "id": 2,
     *      "time_interval_id": 2,
     *      "path": "uploads\/screenshots\/1_1_2.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
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
     * @api             {post} /v1/screenshots/create Create
     * @apiDescription  Create Screenshot
     *
     * @apiVersion     1.0.0
     * @apiName        Create
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer}  time_interval_id  Time Interval ID
     * @apiParam {Binary}   screenshot        File
     *
     * @apiParamExample {json} Simple-Request Example
     *  {
     *    "time_interval_id": 1,
     *    "screenshot": <binary data>
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      User
     *
     * @apiUse ScreenshotObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "id": 1,
     *      "time_interval_id": 1,
     *      "path": "uploads\/screenshots\/1_1_1.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @param Request $request
     * @return JsonResponse
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

        $screenStorePath = $request->screenshot->store('uploads/screenshots');
        $absoluteStorePath = Storage::disk()->path($screenStorePath);
        $path = Filter::process($this->getEventUniqueName('request.item.create'), $absoluteStorePath);

        $screenshot = Image::make($path);

        $thumbnail = $screenshot->resize(280, null, static function ($constraint) {
            $constraint->aspectRatio();
        });

        $thumbnailPath = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);
        Storage::put($thumbnailPath, (string)$thumbnail->encode());

        // Get interval id
        $timeIntervalID = ((int)$request->get('time_interval_id')) ?: null;

        // Pack everything we need
        $screenshotPack = [
            'time_interval_id' => $timeIntervalID,
            'path' => $path,
            'thumbnail_path' => str_replace('.jpg', '-thumb.jpg', $thumbnailPath),
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

    /**
     * @api             {post} /v1/screenshots/remove Destroy
     * @apiDescription  Destroy Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_remove
     * @apiPermission   screenshots_full_access
     *
     * @apiParam {Integer}  id  ID of the target screenshot
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    /**
     * Remove the specified resource from storage
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        if (!isset($request->id)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.remove'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'screenshot id is required',
                ]),
                400);
        }

        // Get screenshot model
        $screenshotModel = $this->getItemClass();

        // Find exact screenshot to be deleted
        $screenshotToDel = $screenshotModel::where('id', $request->get('id'))->firstOrFail();

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

        return response()->json(['success' => true, 'message' => 'Screenshot successfully deleted']);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/screenshots/bulk-create Bulk Create
     * @apiDescription  Create Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Create
     * @apiGroup        Screenshot
     *
     * @apiPermission   screenshots_bulk_create
     * @apiPermission   screenshots_full_access
     */

    /**
     * @api             {post} /v1/screenshots/show Show
     * @apiDescription  Show Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Show
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_show
     * @apiPermission   screenshots_full_access
     *
     * @apiParam {Integer}  id ID
     *
     * @apiUse          ScreenshotParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1,
     *    "time_interval_id": ["=", [1,2,3]],
     *    "path": ["like", "%lorem%"],
     *    "created_at": [">", "2019-01-01 00:00:00"],
     *    "updated_at": ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse          ScreenshotObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *   "id": 1,
     *   "time_interval_id": 1,
     *   "path": "uploads\/screenshots\/1_1_1.png",
     *   "created_at": "2020-01-23T09:42:26+00:00",
     *   "updated_at": "2020-01-23T09:42:26+00:00",
     *   "deleted_at": null,
     *   "thumbnail_path": null,
     *   "important": false,
     *   "is_removed": false
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */

    /**
     * @api             {post} /v1/screenshots/edit Edit
     * @apiDescription  Edit Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Edit
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_edit
     * @apiPermission   screenshots_full_access
     *
     * @apiParam {Integer}  id                ID
     * @apiParam {Integer}  time_interval_id  Time Interval id
     * @apiParam {String}   path              Image path URI
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "time_interval_id": 2,
     *    "path": "test"
     *  }
     *
     * @apiUse         ScreenshotObject
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */

    /**
     * @api             {get,post} /v1/screenshot/count Count
     * @apiDescription  Count Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/screenshots/dashboard Dashboard
     * @apiDescription  Get dashboard of Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         Dashboard
     * @apiGroup        Screenshot
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @deprecated
     * @codeCoverageIgnore
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
                $items[$itemkey]['intervals'][(int) $minutes[0]][] = $interval->toArray();
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

                $arr['intervals'][(int) $minutes[0]][] = $interval->toArray();
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
                    function (Builder $query) use ($user_id, $object, $action) {
                        $query->where('user_id', $user_id)->whereHas('role',
                            function (Builder $query) use ($object, $action) {
                                $query->whereHas('rules', function (Builder $query) use ($object, $action) {
                                    $query->where([
                                        'object' => $object,
                                        'action' => $action,
                                        'allow' => true,
                                    ])->select('id');
                                })->select('id');
                            })->select('id');
                    });

                // For read and delete access include user own intervals
                $query->when($action !== 'edit', function (Builder $query) use ($user_id) {
                    $query->orWhereHas('timeInterval', function (Builder $query) use ($user_id) {
                        $query->where('user_id', $user_id)->select('user_id');
                    });
                });

                $query->when($action === 'edit' && (bool) $user->manual_time,
                    function (Builder $query) use ($user_id) {
                        $query->orWhereHas('timeInterval', function (Builder $query) use ($user_id) {
                            $query->where('user_id', $user_id)->select('user_id');
                        });
                    });
            });
        }

        return $query;
    }
}
