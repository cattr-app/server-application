<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

/**
 * Class ItemController
 *
 * @package App\Http\Controllers\Api\v1
 */
abstract class ItemController extends Controller
{
    /**
     * Returns current item's class name
     *
     * @return string
     */
    abstract public function getItemClass(): string;

    /**
     * Returns validation rules for current item
     *
     * @return array
     */
    abstract public function getValidationRules(): array;

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    abstract public function getEventUniqueNamePart(): string;

    /**
     * Display a listing of the resource.
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $cls = $this->getItemClass();
        $items = $cls::all();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items)
        );
    }

    /**
     * Create item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
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

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::create($this->filterRequestData($requestData))
        );

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ])
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $cls = $this->getItemClass();

        $itemId = Filter::process($this->getEventUniqueName('request.item.show'), $request->get('id'));

        if (\is_array($itemId)) {
            $itemId = $itemId[0];
        }

        $item = $cls::findOrFail($itemId);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.show'), $item)
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $request->all()
        );
        
        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $this->getValidationRules()
            )
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
            ])
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        $cls = $this->getItemClass();
        $itemId = Filter::process(
            $this->getEventUniqueName('request.item.remove'),
            $request->get('id')
        );

        $item = $cls::findOrFail($itemId);
        $item->delete();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'item has been removed'
            ])
        );
    }

    /**
     * Opportunity to filtering request data
     *
     * Override this in child class for filtering
     * @param array $requestData
     * @return array
     */
    protected function filterRequestData(array $requestData): array
    {
        return $requestData;
    }

    /**
     * Returns event's name with current item's unique part
     *
     * @param $eventName
     * @return string
     */
    protected function getEventUniqueName(string $eventName): String
    {
        return "{$eventName}.{$this->getEventUniqueNamePart()}";
    }
}
