<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Screenshot;
use Illuminate\Http\Request;
use Filter;
use Illuminate\Support\Facades\Input;
use Validator;

class ScreenshotController extends ItemController
{
    function getItemClass()
    {
        return Screenshot::class;
    }

    function getValidationRules()
    {
        return [
            'time_interval_id' => 'required',
            'path'             => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'screenshot';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        //$requestData = Filter::fire($this->getEventUniqueName('request.item.create'), $request->all());

        $path = $request->screenshot->store('images');
        $timeIntervalId = (int)$request->time_interval_id;

        $requestData = [
            'time_interval_id' => $timeIntervalId,
            'path' => $path
        ];

        $validator = Validator::make(
            $requestData,
            Filter::fire($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
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
        $item = Filter::fire($this->getEventUniqueName('item.create'), $cls::create($requestData));

        return response()->json(
            Filter::fire($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ]),
            200
        );
    }
}
