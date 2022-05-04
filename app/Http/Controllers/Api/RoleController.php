<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Role\ListRoleRequest;
use Filter;
use App\Models\Role;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class RoleController extends ItemController
{
    protected const MODEL = Role::class;

    /**
     * @param ListRoleRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(ListRoleRequest $request): JsonResponse
    {
        if ($request->get('user_id')) {
            $request->offsetSet('users.id', $request->get('user_id'));
            $request->offsetUnset('user_id');
        }

        return $this->_index($request);
    }

    /**
     * @api             {post} /roles/list List
     * @apiDescription  Get list of Roles
     *
     * @apiVersion      1.0.0
     * @apiName         GetRolesList
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_list
     * @apiPermission   roles_full_access
     *
     * @apiUse          RoleParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": [">", 1]
     *    "name": ["like", "%lorem%"],
     *    "created_at": [">", "2019-01-01 00:00:00"],
     *    "updated_at": ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 256,
     *      "name": "test",
     *      "deleted_at": null,
     *      "created_at": "2018-10-12 11:44:08",
     *      "updated_at": "2018-10-12 11:44:08"
     *    }
     *  ]
     *
     * @apiUse          RoleObject
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @api             {post} /roles/create Create
     * @apiDescription  Create Role
     *
     * @apiVersion      1.0.0
     * @apiName         CreateRole
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_create
     * @apiPermission   roles_full_access
     *
     * @apiParam {String} name Roles's name
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "name": "test"
     *  }
     *
     * @apiSuccess {Object}   res      Response object
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "name": "test",
     *      "updated_at": "2018-10-12 11:44:08",
     *      "created_at": "2018-10-12 11:44:08",
     *      "id": 256
     *    }
     *  }
     *
     * @apiUse          RoleObject
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     */

    /**
     * @throws Exception
     * @api             {get,post} /roles/count Count
     * @apiDescription  Count Roles
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_count
     * @apiPermission   roles_full_access
     *
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function count(ListRoleRequest $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     *
     * @api             {post} /roles/show Show
     * @apiDescription  Get Role Entity
     *
     * @apiVersion      1.0.0
     * @apiName         ShowRole
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_show
     * @apiPermission   roles_full_access
     *
     * @apiParam {Integer}  id  ID
     *
     * @apiUse          RoleParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1,
     *    "name": ["like", "%lorem%"],
     *    "description": ["like", "%lorem%"],
     *    "created_at": [">", "2019-01-01 00:00:00"],
     *    "updated_at": ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse          RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "name": "root",
     *    "deleted_at": null,
     *    "created_at": "2018-09-25 06:15:07",
     *    "updated_at": "2018-09-25 06:15:07"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     * @apiUse          ValidationError
     * @apiUse          ItemNotFoundError
     */

    /**
     * @api             {post} /roles/edit Edit
     * @apiDescription  Edit Role
     *
     * @apiVersion      1.0.0
     * @apiName         EditRole
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_edit
     * @apiPermission   roles_full_access
     *
     * @apiParam {Integer} id   Role ID
     * @apiParam {String}  name Role Name
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1,
     *    "name": "test"
     *  }
     *
     * @apiUse          RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "name": "root",
     *    "deleted_at": null,
     *    "created_at": "2018-09-25 06:15:07",
     *    "updated_at": "2018-09-25 06:15:07"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          ItemNotFoundError
     */

    /**
     * @api             {post} /roles/remove Destroy
     * @apiDescription  Destroy Role
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyRole
     * @apiGroup        Role
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   roles_remove
     * @apiPermission   roles_full_access
     *
     * @apiParam {Integer}  id  Role ID
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
     * @apiUse          ItemNotFoundError
     */

    /**
     * @api             {get,post} /roles/allowed-rules Allowed Rules
     * @apiDescription  Get Rule allowed action for current user list
     *
     * @apiVersion      1.0.0
     * @apiName         GetRulesAllowedActionList
     * @apiGroup        Role
     *
     * @apiParam {Integer} ids Role ids
     *
     * @apiPermission   roles_allowed_rules
     * @apiPermission   roles_full_access
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "ids": 1
     *  }
     *
     * @apiSuccess {Object[]} res      Rules
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res":
     *      [
     *        {
     *          "object": "attached-users",
     *          "action": "bulk-create",
     *          "name": "Attached User relation multiple create"
     *        },
     *        {
     *          "object": "attached-users",
     *          "action": "bulk-remove",
     *          "name": "Attached User relation multiple remove"
     *        }
     *      ]
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {get,post} /roles/project-rules Project Rules
     * @apiDescription  Get Rule allowed action for current user list
     *
     * @apiVersion      1.0.0
     * @apiName         GetRulesProjectActionList
     * @apiGroup        Role
     *
     * @apiPermission   roles_project_rules
     * @apiPermission   roles_full_access
     *
     * @apiParamExample {json} Request Example
     *  {}
     *
     * @apiSuccess {Object}   res      Rules indexed by project ID
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res":
     *      {
     *        "15": [
     *          {
     *            "object": "attached-users",
     *            "action": "bulk-create",
     *            "name": "Attached User relation multiple create"
     *          },
     *          {
     *            "object": "attached-users",
     *            "action": "bulk-remove",
     *            "name": "Attached User relation multiple remove"
     *          }
     *        ]
     *      }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {post} /roles/attach-user Attach User
     * @apiDescription  Attach user to role
     *
     * @apiVersion      1.0.0
     * @apiName         AttachUser
     * @apiGroup        Role
     *
     * @apiParam {Object[]}  relations          New relations of user
     * @apiParam {Integer}   relations.user_id  User ID
     * @apiParam {Integer}   relations.role_id  Role ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "relations": [
     *      {
     *        "user_id": 1,
     *        "role_id": 2
     *      }
     *    ]
     *  }
     *
     * @apiSuccess {Object[]}  res      Relations
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [
     *      {
     *        "object": "attached-users",
     *        "action": "bulk-create",
     *        "name": "Attached User relation multiple create"
     *      },
     *      {
     *        "object": "attached-users",
     *        "action": "bulk-remove",
     *        "name": "Attached User relation multiple remove"
     *      }
     *    ]
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */

    /**
     * @api             {post} /roles/detach-user Detach User
     * @apiDescription  Detach user from role
     *
     * @apiVersion      1.0.0
     * @apiName         DetachUser
     * @apiGroup        Role
     *
     * @apiParam {Object[]}  relations          Relations of user that must be removed
     * @apiParam {Integer}   relations.user_id  User ID
     * @apiParam {Integer}   relations.role_id  Role ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "relations": [
     *      {
     *        "user_id": 1,
     *        "role_id": 2
     *      }
     *    ]
     *  }
     *
     * @apiSuccess {Object[]}  res      Relations
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": [
     *      {
     *        "object": "attached-users",
     *        "action": "bulk-create",
     *        "name": "Attached User relation multiple create"
     *      },
     *      {
     *        "object": "attached-users",
     *        "action": "bulk-remove",
     *        "name": "Attached User relation multiple remove"
     *      }
     *    ]
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */
}
