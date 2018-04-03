<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return [
            'user', 'project', 'assigned',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $filter = $request->all() ?: [];
        $filter['user_id'] = $user->id;

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter($this->getQuery(), $filter)
        );

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }
}
