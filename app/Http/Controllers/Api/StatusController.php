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
     * @throws Throwable
     * @api             {post} /statuses/show Show
     * @apiDescription  Show status.
     *
     * @apiVersion      4.0.0
     * @apiName         Show Status
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} id  Status ID
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiUse StatusObject
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function show(ShowStatusRequestStatus $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @throws Throwable
     * @api             {get} /statuses/list List
     * @apiDescription  Get list of statuses.
     *
     * @apiVersion      4.0.0
     * @apiName         Status List
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Object}   res      Status
     *
     * @apiUse StatusObject
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function index(ListStatusRequest $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @param CreateStatusRequest $request
     * @return JsonResponse
     * @throws Throwable
     * @api             {post} /statuses/create Create
     * @apiDescription  Creates status
     *
     * @apiVersion      4.0.0
     * @apiName         Create Status
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {String}  name   Status name
     * @apiParam {String}  active Status active
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "name": "Normal",
     *    "active": false
     *  }
     *
     * @apiSuccess {Object}   res      Status
     *
     * @apiSuccess {Number}   id             The ID of the status.
     * @apiSuccess {String}   name           The name of the status.
     * @apiSuccess {Boolean}  active         Indicates if the status is active.\
     * @apiSuccess {String}   created_at     The creation timestamp.
     * @apiSuccess {String}   updated_at     The last update timestamp.
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *   "id": 10,
     *   "name": "Normal",
     *   "active": false,
     *   "created_at": "2024-08-15T14:04:03.000000Z",
     *   "updated_at": "2024-08-15T14:04:03.000000Z"
     * }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
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
     * @throws Throwable
     * @api             {post} /statuses/edit Edit
     * @apiDescription  Edit Status
     *
     * @apiVersion      4.0.0
     * @apiName         Edit
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id           ID
     * @apiParam {String}   name    Status name
     * @apiParam {String}   active  Status active
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "name": "Normal",
     *    "active": false
     *  }
     *
     * @apiSuccess {Object}   res      Status
     *
     * @apiUse         StatusObject
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
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
     * @throws Throwable
     * @api             {post} /statuses/remove Destroy
     * @apiDescription  Destroy User
     *
     * @apiVersion      4.0.0
     * @apiName         Destroy Status
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id  ID of the target status
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess (204) No Content  Indicates that the status was successfully removed or deactivated.
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function destroy(DestroyStatusRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @param ListStatusRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    /**
     * @api            {get} /invitations/count Count Invitations
     * @apiDescription Get the count of invitations
     *
     * @apiVersion     4.0.0
     * @apiName        CountInvitations
     * @apiGroup       Invitations
     *
     * @apiSuccess {Integer} total The total count of pending invitations.
     *
     * @apiSuccessExample {json} Success Response:
     * {
     *     "total": 0
     * }
     *
     * @apiUse TotalSuccess
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function count(ListStatusRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
