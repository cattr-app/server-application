<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Screenshot;
use Auth;
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
     * @api {post} /api/v1/screenshots/list List
     * @apiDescription Get list of Screenshots
     * @apiVersion 0.1.0
     * @apiName GetScreenshotList
     * @apiGroup Screenshot
     *
     * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
     * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's TimeInterval's User ID
     * @apiParam {String}   [path]             `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
     * @apiParam {DateTime} [deleted_at]       `QueryParam` When Screenshot was deleted (null if not)
     *
     * @apiSuccess (200) {Screenshot[]} ScreenshotList array of Screenshot objects
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('user_id') ? $filters['timeInterval.user_id'] = $request->get('user_id') : False;

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
     * Show the form for creating a new resource.
     *
     * @api {post} /api/v1/screenshots/create Create
     * @apiDescription Create Screenshot
     * @apiVersion 0.1.0
     * @apiName CreateScreenshot
     * @apiGroup Screenshot
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
     */

    /**
     * @api {post} /api/v1/screenshots/edit Edit
     * @apiDescription Edit Screenshot
     * @apiVersion 0.1.0
     * @apiName EditScreenshot
     * @apiGroup Screenshot
     */

    /**
     * @api {post} /api/v1/screenshots/destroy Destroy
     * @apiDescription Destroy Screenshot
     * @apiVersion 0.1.0
     * @apiName DestroyScreenshot
     * @apiGroup Screenshot
     * @apiParam {Integer} [user_id] `QueryParam` Screenshot's User ID
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
     * @apiParam {Integer} [id] `QueryParam` Screenshot ID
     * @apiParam {Integer} [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer} [user_id] `QueryParam` Screenshot's User ID
     * @apiParam {String} [path] `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at] `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Screenshot data update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Screenshot was deleted (null if not)
     *
     * @apiSuccess (200) {Screenshots[]} ScreenshotList array of Screenshot objects and interval
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->all();
        is_int($request->get('user_id')) ? $filters['timeInterval.user_id'] = $request->get('user_id') : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery->orderBy('created_at', 'desc')
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

            preg_match('/(\d{4}-\d{2}-\d{2} \d{2})/', $screenshot->created_at, $matches);

            $hour = $matches[1].':00:00';

            foreach ($items as $itemkey => $item) {
                if($item['interval'] == $hour) {
                    $hasInterval = true;
                    break;
                }
            }

            if($hasInterval) {
                $items[$itemkey]['screenshots'][] = $screenshot->toArray();
            } else {
                $items[] = [
                    'interval' => $hour,
                    'screenshots' => [$screenshot],
                ];
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
