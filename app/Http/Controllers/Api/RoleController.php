<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use CatEvent;
use Filter;
use Illuminate\Http\JsonResponse;

class RoleController extends ItemController
{
    /**
     * @api             {post} /api/roles/list Get Roles List
     * @apiDescription  Retrieves the list of roles available in the system.
     *
     * @apiVersion      4.0.0
     * @apiName         GetRolesList
     * @apiGroup        Roles
     *
     * @apiSuccess {Number}   status             HTTP status code.
     * @apiSuccess {Boolean}  success            Request success status.
     * @apiSuccess {Object[]} data               List of roles.
     * @apiSuccess {String}   data.name          Role name.
     * @apiSuccess {Number}   data.id            Role ID.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": [
     *      {
     *        "name": "ANY",
     *        "id": -1
     *      },
     *      {
     *        "name": "ADMIN",
     *        "id": 0
     *      },
     *      {
     *        "name": "MANAGER",
     *        "id": 1
     *      },
     *      {
     *        "name": "USER",
     *        "id": 2
     *      },
     *      {
     *        "name": "AUDITOR",
     *        "id": 3
     *      }
     *    ]
     *  }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     * @apiUse ForbiddenError
     */
    public function index(): JsonResponse
    {
        CatEvent::dispatch(Filter::getBeforeActionEventName());

        $items = Filter::process(
            Filter::getActionFilterName(),
            //For compatibility reasons generate serialized model-like array
            array_map(fn ($role) => ['name' => $role->name, 'id' => $role->value], Role::cases()),
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items]);

        return responder()->success($items)->respond();
    }
}
