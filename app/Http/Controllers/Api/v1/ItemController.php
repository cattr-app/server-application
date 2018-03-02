<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Task;
use Illuminate\Http\Request;

abstract class ItemController extends Controller
{
    abstract function getItemClass();

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $keyword = $request->get('search');
        $perPage = 25;

        $cls = $this->getItemClass();

        if (!empty($keyword)) {
            $items = $cls::paginate($perPage);
        } else {
            $items = $cls::paginate($perPage);
        }

        return response()->json(
            $items, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $cls = $this->getItemClass();
        $requestData = $request->all();
        $item = $cls::create($requestData);

        return response()->json([
            'res' => $item,
        ], 200);
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
        $itemId = $request->get('id');
        $item = $cls::findOrFail($itemId);

        return response()->json($item, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $cls = $this->getItemClass();
        $itemId = $request->get('id');
        $item = $cls::findOrFail($itemId);

        $item->fill($request->all());
        $item->save();

        return response()->json([
            'item' => $item,
        ], 200);
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
        $itemId = $request->get('id');

        $item = $cls::findOrFail($itemId);
        $item->delete();

        return response()->json(['message' => 'item has been removed']);
    }
}
