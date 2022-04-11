<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Priority\CountPriorityRequestCattr;
use App\Http\Requests\Priority\CreatePriorityRequestCattr;
use App\Http\Requests\Priority\ShowPriorityRequestCattr;
use App\Http\Requests\Priority\UpdatePriorityRequestCattr;
use App\Http\Requests\Project\DestroyProjectRequestCattr;
use App\Models\Priority;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriorityController extends ItemController
{
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
        return 'priority';
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getItemClass(): string
    {
        return Priority::class;
    }

    /**
     * @throws Exception
     * @api             {post} /priorities/show Show
     * @apiDescription  Show priority.
     *
     * @apiVersion      1.0.0
     * @apiName         Show Priority
     * @apiGroup        Priority
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer} id  Priority ID
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Object}   res      Priority
     *
     * @apiUse          PriorityObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "color": null
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function show(ShowPriorityRequestCattr $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @throws Exception
     * @api             {get} /priorities/list List
     * @apiDescription  Get list of priorities.
     *
     * @apiVersion      1.0.0
     * @apiName         Priority List
     * @apiGroup        Priority
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Object}   res      Priority
     *
     * @apiUse          PriorityObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [{
     *      "id": 1
     *      "name": "Normal",
     *      "color": null
     *    }]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function index(Request $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @param CreatePriorityRequestCattr $request
     * @return JsonResponse
     * @api             {post} /priorities/create Create
     * @apiDescription  Creates priority
     *
     * @apiVersion      1.0.0
     * @apiName         Create Priority
     * @apiGroup        Priority
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {String}  name   Priority name
     * @apiParam {String}  color  Priority color
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "name": "Normal",
     *    "color": null
     *  }
     *
     * @apiSuccess {Object}   res      Priority
     *
     * @apiUse          PriorityObject
     *
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "color": null
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     */
    public function create(CreatePriorityRequestCattr $request): JsonResponse
    {
        return $this->_create($request);
    }

    /**
     * @throws Exception
     * @api             {post} /priorities/edit Edit
     * @apiDescription  Edit Priority
     *
     * @apiVersion      1.0.0
     * @apiName         Edit
     * @apiGroup        Priority
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id           ID
     * @apiParam {String}   name   Priority name
     * @apiParam {String}   color  Priority color
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1,
     *    "name": "Normal",
     *    "color": null
     *  }
     *
     * @apiSuccess {Object}   res      Priority
     *
     * @apiUse          PriorityObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1
     *      "name": "Normal",
     *      "color": null
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */
    public function edit(UpdatePriorityRequestCattr $request): JsonResponse
    {
        return $this->_edit($request);
    }

    /**
     * @throws Exception
     * @api             {post} /priorities/remove Destroy
     * @apiDescription  Destroy User
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy Priority
     * @apiGroup        Priority
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  id  ID of the target priority
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
    public function destroy(DestroyProjectRequestCattr $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @param CountPriorityRequestCattr $request
     * @return JsonResponse
     * @throws Exception
     */
    public function count(CountPriorityRequestCattr $request): JsonResponse
    {
        return $this->_count($request);
    }
}
