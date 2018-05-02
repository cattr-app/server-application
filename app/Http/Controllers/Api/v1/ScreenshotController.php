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
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $path = Filter::process($this->getEventUniqueName('request.item.create'), $request->get('screenshot')->store('uploads/screenshots'));
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
