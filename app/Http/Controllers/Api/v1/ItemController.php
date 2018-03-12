<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Filter;
use Illuminate\Http\Request;
use Validator;

abstract class ItemController extends Controller
{
    /**
     * Returns current item's class name
     *
     * @return string
     */
    abstract function getItemClass();

    /**
     * Returns validation rules for current item
     *
     * @return array
     */
    abstract function getValidationRules();

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    abstract function getEventUniqueNamePart();

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        //TODO: add pagination on front

        $keyword = Filter::process($this->getEventUniqueName('request.item.list'), $request->get('search'));

        $perPage = 200;

        $cls = $this->getItemClass();

        if (!empty($keyword)) {
            $items = $cls::paginate($perPage);
        } else {
            $items = $cls::paginate($perPage);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

    /**
     * Create item
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
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
            ]),
            200
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $cls = $this->getItemClass();

        $itemId = Filter::process($this->getEventUniqueName('request.item.show'), $request->get('id'));

        if (is_array($itemId)) {
            $itemId = $itemId[0];
        }

        $item = $cls::findOrFail($itemId);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.show'), $item),
            200
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.edit'), $request->all());
        
        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.edit'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'validation fail',
                ]),
                400
            );
        }

        $cls = $this->getItemClass();
        $itemId = $request->get('id');
        $item = $cls::findOrFail($itemId);

        $item->fill($requestData);
        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ]),
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request)
    {
        $cls = $this->getItemClass();
        $itemId = Filter::process($this->getEventUniqueName('request.item.remove'), $request->get('id'));

        $item = $cls::findOrFail($itemId);
        $item->delete();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'item has been removed'
            ]),
            200
        );
    }

    /**
     * Returns event's name with current item's unique part
     *
     * @param $eventName
     * @return string
     */
    protected function getEventUniqueName($eventName)
    {
        return $eventName . '.' . $this->getEventUniqueNamePart();
    }
}
