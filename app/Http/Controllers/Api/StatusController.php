<?php

namespace App\Http\Controllers\Api;

use App\Models\Status;
use App\Http\Requests\Status\CreateStatusRequest;
use App\Http\Requests\Status\DestroyStatusRequest;
use App\Http\Requests\Status\ListStatusRequest;
use App\Http\Requests\Status\ShowStatusRequestStatus;
use App\Http\Requests\Status\UpdateStatusRequest;
use CatEvent;
use Exception;
use Filter;
use Illuminate\Http\JsonResponse;
use Throwable;

class StatusController extends ItemController
{
    protected const MODEL = Status::class;

    /**
     * Show
     *
     * Show status.
     */
    public function show(ShowStatusRequestStatus $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * List
     *
     * Get list of statuses.
     */
    public function index(ListStatusRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * Create
     *
     * Creates status
     */
    public function create(CreateStatusRequest $request): JsonResponse
    {
        Filter::listen(Filter::getRequestFilterName(), static function ($item) {
            $maxOrder = Status::max('order');
            $item['order'] = $maxOrder + 1;
            return $item;
        });

        return $this->_create($request);
    }

    /**
     * Edit
     *
     * Edit Status
     */
    public function edit(UpdateStatusRequest $request): JsonResponse
    {
        CatEvent::listen(Filter::getBeforeActionEventName(), static function ($item, $requestData) {
            if (isset($requestData['order'])) {
                $newOrder = $requestData['order'];
                $oldOrder = $item->order;
                if ($newOrder < 1) {
                    $newOrder = 1;
                }
                $maxOrder = Status::max('order');
                if ($newOrder > $maxOrder) {
                    $newOrder = $maxOrder + 1;
                }
                $swapItem = Status::where('order', '=', $newOrder)->first();
                if (isset($swapItem)) {
                    $swapItemOrder = $swapItem->order;

                    $item->order = 0;
                    $item->save();

                    $swapItem->order = $oldOrder;
                    $swapItem->save();

                    $item->order = $swapItemOrder;
                    $item->save();
                } else {
                    $item->order = $newOrder;
                }
            }
        });
        return $this->_edit($request);
    }


    /**
     * Destroy
     *
     * Destroy User
     */
    public function destroy(DestroyStatusRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * Count Invitations
     *
     * Get the count of invitations
     */
    public function count(ListStatusRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
