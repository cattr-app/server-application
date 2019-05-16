<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Screenshot;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Filter;
use Illuminate\Support\Facades\Input;
use Validator;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Route;

/**
 * Class ScreenshotController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ScreenshotController extends ItemController
{
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
            'path'             => 'required',
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
     * @api {post} /api/v1/screenshots/list List
     * @apiDescription Get list of Screenshots
     * @apiVersion 0.1.0
     * @apiName GetScreenshotList
     * @apiGroup Screenshot
     *
     * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's TimeInterval's User ID
     * @apiParam {Integer}  [project_id]       `QueryParam` Screenshot's TimeInterval's Project ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse ScreenshotRelations
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
     * @apiUse ScreenshotRelationsExample
     * @apiUser UnauthorizedError
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
     * @param Request $request
     * @return JsonResponse
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
     * @api {post} /api/v1/screenshots/create Create
     * @apiDescription Create Screenshot
     * @apiVersion 0.1.0
     * @apiName CreateScreenshot
     * @apiGroup Screenshot
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
     * @apiUse DefaultCreateErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        if (!isset($request->screenshot)) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.create'), [
                    [
                        'error' => 'validation fail',
                        'reason' => 'screenshot is required',
                    ]
                ]),
                400
            );
        }

        $screenStorePath = $request->screenshot->store('uploads/screenshots');
        $absoluteStorePath = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, Storage::disk()->path($screenStorePath));

        $path = Filter::process($this->getEventUniqueName('request.item.create'), $absoluteStorePath);

        $screenshot = Image::make($path);

        $thumbnail = $screenshot->resize(280, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        $ds = DIRECTORY_SEPARATOR;

        $thumbnailPath = str_replace("uploads{$ds}screenshots", "uploads{$ds}screenshots{$ds}thumbs", $screenStorePath);
        Storage::put($thumbnailPath, (string) $thumbnail->encode());

        $timeIntervalId = ((int) $request->get('time_interval_id')) ?: null;

        $requestData = [
            'time_interval_id' => $timeIntervalId,
            'path' => $screenStorePath,
            'thumbnail_path' => $thumbnailPath,
        ];

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.create'), [
                    [
                        'error' => 'validation fail',
                        'reason' => $validator->errors()
                    ]
                ]),
                400
            );
        }

        $cls = $this->getItemClass();
        $item = Filter::process($this->getEventUniqueName('item.create'), $cls::create($requestData));

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api {post} /api/v1/screenshots/show Show
     * @apiDescription Show Screenshot
     * @apiVersion 0.1.0
     * @apiName ShowScreenshot
     * @apiGroup Screenshot
     *
     * @apiParam {Integer}  id                              Screenshot id
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot Time Interval id
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {String}   [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {String}   [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse ScreenshotRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":               1,
     *      "time_interval_id": ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse ScreenshotRelationsExample
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
     * @apiUse DefaultShowErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/screenshots/edit Edit
     * @apiDescription Edit Screenshot
     * @apiVersion 0.1.0
     * @apiName EditScreenshot
     * @apiGroup Screenshot
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
     * @apiUse DefaultEditErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/screenshots/remove Destroy
     * @apiUse DefaultDestroyRequestExample
     * @apiDescription Destroy Screenshot
     * @apiVersion 0.1.0
     * @apiName DestroyScreenshot
     * @apiGroup Screenshot
     *
     * @apiParam {String} id Screenshot id
     *
     * @apiUse DefaultDestroyResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/screenshots/dashboard Dashboard
     * @apiDescription Get dashboard of Screenshots
     * @apiVersion 0.1.0
     * @apiName GetScreenshotDashboard
     * @apiGroup Screenshot
     *
     * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's User ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse ScreenshotRelations
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
     * @apiUse ScreenshotRelationsExample
     * @apiUse UnauthorizedError
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $timezone = Auth::user()->timezone;
        if (!$timezone) {
            $timezone = 'UTC';
        }

        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['timeInterval.user_id'] = $request->get('user_id') : False;
        $compareDate = Carbon::today($timezone)->setTimezone('UTC')->toDateTimeString();
        $filters['timeInterval.start_at'] = ['>=', [$compareDate]];

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery->orderBy('id', 'desc')
        );

        $screenshots = $itemsQuery->get();

        if (collect($screenshots)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        $items = [];

        foreach ($screenshots as $screenshot) {
            $hasInterval = false;
            $matches = [];

            preg_match('/(\d{4}-\d{2}-\d{2} \d{2})/', $screenshot->timeInterval->start_at, $matches);
            $minutes = Carbon::parse($screenshot->timeInterval->start_at)->minute;
            $minutes = $minutes > 9 ? (string)$minutes : '0'.$minutes;
            $hour = $matches[1].':00:00';

            foreach ($items as $itemkey => $item) {
                if($item['interval'] == $hour) {
                    $hasInterval = true;
                    break;
                }
            }

            if($hasInterval && isset($itemkey)) {
                $items[$itemkey]['screenshots'][(int)$minutes{0}][] = $screenshot->toArray();
            } else {
                $arr = [
                    'interval' => $hour,
                    'screenshots' => [
                        0 => [],
                        1 => [],
                        2 => [],
                        3 => [],
                        4 => [],
                        5 => [],
                    ]
                ];
                $arr['screenshots'][(int)$minutes{0}][] = $screenshot->toArray();
                $items[] = $arr;
            }
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'screenshots', 'full_access');
        $relations_access = Role::can(Auth::user(), 'users', 'relations');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $user_time_interval_id = collect(Auth::user()->timeIntervals)->flatMap(function($val) {
            return collect($val->id);
        });

        $time_intervals_id = collect([$user_time_interval_id])->collapse();
        if ($action_method !== 'remove'
            || $action_method === 'remove'
            && Role::can(Auth::user(), 'remove', 'remove_related')) {
            if ($project_relations_access) {
                $attached_time_interval_id_to_project = collect(Auth::user()->projects)->flatMap(function ($project) {
                    return collect($project->tasks)->flatMap(function ($task) {
                        return collect($task->timeIntervals)->pluck('id');
                    });
                });

                $time_intervals_id = collect([$time_intervals_id, $attached_time_interval_id_to_project])->collapse()->unique();
            }

            if ($relations_access) {
                $attached_users_time_intervals_id = collect(Auth::user()->attached_users)->flatMap(function($val) {
                    return collect($val->timeIntervals)->pluck('id');
                });

                $time_intervals_id = collect([$time_intervals_id, $attached_users_time_intervals_id])->collapse()->unique();
            }
        }

        $query->whereIn('screenshots.time_interval_id', $time_intervals_id);

        return $query;
    }
}
