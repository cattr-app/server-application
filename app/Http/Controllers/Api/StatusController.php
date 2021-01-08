<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Status\CountStatusRequest;
use App\Http\Requests\Status\CreateStatusRequest;
use App\Http\Requests\Status\ListStatusRequest;
use App\Http\Requests\Status\ShowStatusRequest;
use App\Http\Requests\Status\UpdateStatusRequest;
use App\Http\Requests\Status\DestroyStatusRequest;
use App\Models\Status;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends ItemController
{
    /**
     * StatusController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * Get the event unique name part.
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'status';
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getItemClass(): string
    {
        return Status::class;
    }

    /**
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
    public function show(ShowStatusRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
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
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     *
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
     *
     */
    public function create(CreateStatusRequest $request): JsonResponse
    {
        return $this->_create($request);
    }

    /**
     * @throws Exception
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
        return $this->_edit($request);
    }

    /**
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
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function count(CountStatusRequest $request): JsonResponse
    {
        return $this->_count($request);
    }
}
