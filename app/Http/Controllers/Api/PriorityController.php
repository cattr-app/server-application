<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Priority\CreatePriorityRequest;
use App\Http\Requests\Priority\DestroyPriorityRequest;
use App\Http\Requests\Priority\ListPriorityRequest;
use App\Http\Requests\Priority\ShowPriorityRequest;
use App\Http\Requests\Priority\UpdatePriorityRequest;
use App\Models\Priority;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class PriorityController extends ItemController
{
    protected const MODEL = Priority::class;

    /**
     * Show
     *
     * Show priority.
     */
    public function show(ShowPriorityRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * List
     *
     * Get list of priorities.
     */
    public function index(ListPriorityRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Create
     *
     * Creates priority
     */
    public function create(CreatePriorityRequest $request): JsonResponse
    {
        return $this->_create($request);
    }

    /**
     * Edit
     *
     * Edit Priority
     */
    public function edit(UpdatePriorityRequest $request): JsonResponse
    {
        return $this->_edit($request);
    }

    /**
     * Destroy
     *
     * Destroy User
     */
    public function destroy(DestroyPriorityRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Count Priorities
     *
     * Returns the total count of priorities
     *
     * @param ListPriorityRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function count(ListPriorityRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
