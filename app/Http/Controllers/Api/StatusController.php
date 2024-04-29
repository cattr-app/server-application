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
     * @apiVersion      1.0.0
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
     * @apiSuccess {Object}   res      Status
     *
     * @apiUse StatusObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "active": false
     *    }
     *  }
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
     * @apiVersion      1.0.0
     * @apiName         Status List
     * @apiGroup        Status
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Object}   res      Status
     *
     * @apiUse StatusObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [{
     *      "id": 1
     *      "name": "Normal",
     *      "active": false
     *    }]
     *  }
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
     * @apiVersion      1.0.0
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
     * @apiUse StatusObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "active": false
     *    }
     *  }
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
     * @apiVersion      1.0.0
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
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "active": false
     *    }
     *  }
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
                $order = $requestData['order'];
                if ($order < 1) {
                    $order = 1;
                }
                $maxOrder = Status::max('order');
                if ($order > $maxOrder) {
                    $order = $maxOrder + 1;
                }
                $nextItem = Status::where('order', '=', $requestData['order'])->first();
                if (isset($nextItem)) {
                    $itemOrder = $nextItem->order;
                    Status::where('order', '=',  $item->order)->update(['order' => 0]);
                    $nextItem->order = $item->order;
                    $item->order = $itemOrder;
                    $nextItem->save();
                    Status::where('order', '=', 0)->update(['order' => $requestData['order']]);
                } else {
                    $item->order = $maxOrder + 1;
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
     * @apiVersion      1.0.0
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
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Item has been removed"
     *  }
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
    public function count(ListStatusRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
