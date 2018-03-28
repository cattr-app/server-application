<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;
use Illuminate\Http\Request;
use Filter;

/**
 * Class TaskController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TaskController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Task::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id'  => 'required',
            'task_name'   => 'required',
            'active'      => 'required',
            'user_id'     => 'required',
            'assigned_by' => 'required',
            'url'         => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'task';
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $items = Task::where('user_id', '=', $user->id)->get();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }
}
