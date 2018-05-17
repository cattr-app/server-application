<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Screenshot;
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
     * @apiParam {Integer} [id] `QueryParam` Screenshot ID
     * @apiParam {Integer} [time_interval_id] `QueryParam` Screenshot's Time Interval ID
     * @apiParam {Integer} [user_id] `QueryParam` Screenshot's User ID
     * @apiParam {String} [path] `QueryParam` Image path URI
     * @apiParam {DateTime} [created_at] `QueryParam` Screenshot Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Screenshot data update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Screenshot was deleted (null if not)
     *
     * @apiSuccess (200) {Screenshot[]} ScreenshotList array of Screenshot objects
     */
    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->get('user_id');

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(),
            $request->all() ?: []
        );

        if (is_int($userId) && $userId !== 0) {
            $baseQuery = $baseQuery->whereHas('timeInterval', function($q) use ($userId) {
                $q->whereHas('task', function($qq) use ($userId) {
                    $qq->where('user_id', '=', $userId);
                });
            });
        }

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
        $timeIntervalId = (int) $request->get('time_interval_id');

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
     * Returns screenshot for time interval
     *
     * [pass interval_id param in request]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getScreenshotByIntervalId(Request $request): JsonResponse
    {
        $timeIntervalId = $request->get('interval_id');
        $screenshot =  Screenshot::where('time_interval_id', '=', $timeIntervalId)->first();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.get'), $screenshot)
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
     */

    public function dashboard(): JsonResponse
    {
        $limit = request()->limit;
        $offset = request()->offset;

        $screenshots = Screenshot::whereHas('timeInterval', function ($query) {


                    $query->whereHas('task', function ($query) {
                            $query->where('user_id', auth()->user()->id);
                        }
                    );

                }
            )
            ->orderBy('created_at','desc')
            ->skip($offset)
            ->take($limit)
            ->get();

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
}
