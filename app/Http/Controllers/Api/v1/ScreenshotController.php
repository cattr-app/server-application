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
            'time_interval_id' => 'required',
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
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "id":               [">", 1],
     *      "time_interval_id": ["=", [1,2,3]],
     *      "user_id":          ["=", [1,2,3]],
     *      "project_id":       ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse ScreenshotRelationsExample
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
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "time_interval_id": 1,
     *      "screenshot": ```binary data```
     *  }
     *
     * @apiSuccess {Object}   Screenshot                  Screenshot object
     * @apiSuccess {Integer}  Screenshot.id               Screenshot's ID
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot's Time Interval ID
     * @apiSuccess {String}   Screenshot.path             Screenshot's Image path URI
     * @apiSuccess {DateTime} Screenshot.created_at       Screenshot's date time of create
     * @apiSuccess {DateTime} Screenshot.updated_at       Screenshot's date time of update
     *
     * @apiUse DefaultCreateErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $path = Filter::process($this->getEventUniqueName('request.item.create'), $request->screenshot->store('uploads/screenshots'));
        $timeIntervalId = is_int($request->get('time_interval_id')) ? $request->get('time_interval_id') : null;

        $requestData = [
            'time_interval_id' => $timeIntervalId,
            'path' => $path
        ];

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::fire($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'validation fail',
                    'reason' => $validator->errors()
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
     * @apiParam {Integer}  id                              Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiUse ScreenshotRelations
     *
     * @apiParamExample {json} Simple-Request-Example:
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
     * @apiSuccess {Integer}  Screenshot.id               Screenshot's ID
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot's Time Interval ID
     * @apiSuccess {String}   Screenshot.path             Screenshot's Image path URI
     * @apiSuccess {DateTime} Screenshot.created_at       Screenshot's date time of create
     * @apiSuccess {DateTime} Screenshot.updated_at       Screenshot's date time of update
     * @apiSuccess {DateTime} Screenshot.deleted_at       Screenshot's date time of delete
     * @apiSuccess {Object}   Screenshot.time_interval    Screenshot's Task
     *
     * @apiUse DefaultShowErrorResponse
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
     * @apiParam {Integer}  id               Screenshot ID
     * @apiParam {Integer}  time_interval_id Screenshot's Time Interval ID
     * @apiParam {String}   path             Image path URI
     * @apiParam {DateTime} [created_at]     Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]     Last Screenshot data update DataTime
     *
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "id":               1,
     *      "time_interval_id": 2,
     *      "path":             "test"
     *  }
     *
     * @apiSuccess {Object}   Screenshot                  Screenshot object
     * @apiSuccess {Integer}  Screenshot.id               Screenshot's ID
     * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot's Time Interval ID
     * @apiSuccess {String}   Screenshot.path             Screenshot's Image path URI
     * @apiSuccess {DateTime} Screenshot.created_at       Screenshot's date time of create
     * @apiSuccess {DateTime} Screenshot.updated_at       Screenshot's date time of update
     * @apiSuccess {DateTime} Screenshot.deleted_at       Screenshot's date time of delete
     *
     * @apiUse DefaultEditErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/screenshots/destroy Destroy
     * @apiUse DefaultDestroyRequestExample
     * @apiDescription Destroy Screenshot
     * @apiVersion 0.1.0
     * @apiName DestroyScreenshot
     * @apiGroup Screenshot
     *
     * @apiParam {String} id Screenshot's id
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
     * @apiParamExample {json} Simple-Request-Example:
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
     *
     * @apiSuccess {Object[]} Array                                            Array of objects
     * @apiSuccess {DateTime} Array.object.interval                            Time of interval
     * @apiSuccess {Object[]} Array.object.screenshots                         Screenshots of interval (Array of objects, 6 indexes)
     * @apiSuccess {Integer}  Array.object.screenshots.object.id               Screenshot's ID
     * @apiSuccess {Integer}  Array.object.screenshots.object.time_interval_id Screenshot's Time Interval ID
     * @apiSuccess {String}   Array.object.screenshots.object.path             Screenshot's Image path URI
     * @apiSuccess {DateTime} Array.object.screenshots.object.created_at       Screenshot's date time of create
     * @apiSuccess {DateTime} Array.object.screenshots.object.updated_at       Screenshot's date time of update
     * @apiSuccess {DateTime} Array.object.screenshots.object.deleted_at       Screenshot's date time of delete
     * @apiSuccess {Object}   Array.object.screenshots.object.time_interval    Screenshot's Task
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['timeInterval.user_id'] = $request->get('user_id') : False;
        $YersterdayTimestamp = time() - 60 /* sec */ * 60  /* min */ * 24 /* hours */;
        $compareDate = date("Y-m-d H:i:s", $YersterdayTimestamp );
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
                $items[$itemkey]['screenshots'][(int)$minutes{0}] = $screenshot->toArray();
            } else {
                $arr = [
                    'interval' => $hour,
                    'screenshots' => [
                        0 => '',
                        1 => '',
                        2 => '',
                        3 => '',
                        4 => '',
                        5 => '',
                    ]
                ];
                $arr['screenshots'][(int)$minutes{0}] = $screenshot->toArray();
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

        $query->whereIn('screenshots.time_interval_id', $time_intervals_id);

        return $query;
    }
}
