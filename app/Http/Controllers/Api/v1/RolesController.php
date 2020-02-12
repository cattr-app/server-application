<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Rule;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

/**
 * Class RolesController
 */
class RolesController extends ItemController
{
    protected $disableQueryRoleCheck = false;

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Role::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'role';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'roles.list',
            'count' => 'roles.list',
            'create' => 'roles.create',
            'edit' => 'roles.edit',
            'show' => 'roles.show',
            'destroy' => 'roles.remove',
            'allowedRules' => 'roles.allowed-rules',
            'projectRules' => 'roles.allowed-rules',
            'attachToUser' => 'roles.attach-user',
            'detachFromUser' => 'roles.detach-user',
        ];
    }

    /**
     * @api             {post} /v1/roles/list List
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
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        $cls = $this->getItemClass();
        //$cls::updateRules();

        if ($request->get('user_id')) {
            $request->offsetSet('users.id', $request->get('user_id'));
            $request->offsetUnset('user_id');
        }

        return parent::index($request);
    }

    /**
     * @api             {post} /v1/roles/create Create
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      Response object
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @api             {get,post} /v1/roles/count Count
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @api             {post} /v1/roles/show Show
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
     * @api             {post} /v1/roles/edit Edit
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
     * @api             {post} /v1/roles/remove Destroy
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @api             {get,post} /v1/roles/allowed-rules Allowed Rules
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object[]} res      Rules
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @param Request $request
     * @return JsonResponse
     */
    public function allowedRules(Request $request): JsonResponse
    {
        $roleIds = Filter::process($this->getEventUniqueName('request.item.allowed-rules'), $request->get('ids', []));
        if (is_numeric($roleIds)) {
            $roleIds = [$roleIds];
        }

        $user = $request->user();
        if (!$roleIds || !is_array($roleIds) || empty($roleIds)) {
            $roleIds = [$user->role_id];
        }

        if ($request->input('with_project_roles', false)) {
            foreach ($user->projectsRelation as $relation) {
                $roleIds[] = $relation->role_id;
            }
        }

        /** @var Builder $itemsQuery */
        $this->disableQueryRoleCheck = true;
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->getQuery()
        );
        $this->disableQueryRoleCheck = false;

        $itemsQuery->with('rules');

        $roles = $user->is_admin ? $itemsQuery->get() : $itemsQuery->whereIn('role.id', $roleIds)->get();

        if (!$roles->count()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.allowed-rules'),
                [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'Roles not found'
                ]),
                400
            );
        }

        $items = [];
        $actionList = Rule::getActionList();

        foreach ($roles as $role) {
            /** @var Role $role */
            /** @var Rule[] $rules */
            $rules = $role->rules;

            foreach ($rules as $rule) {
                if (!$rule->allow) {
                    continue;
                }

                $name = $actionList[$rule->object][$rule->action] ?? "$rule->object/$rule->action";
                if (!array_key_exists($name, $items)) {
                    $items[$name] = [
                        'object' => $rule->object,
                        'action' => $rule->action,
                        'name' => $name,
                    ];
                }
            }
        }


        return response()->json([ 'success' => true, 'res' => Filter::process(
            $this->getEventUniqueName('answer.success.item.allowed-rules'),
            $items
        )]);
    }

    /**
     * @api             {get,post} /v1/roles/project-rules Project Rules
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      Rules indexed by project ID
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @param Request $request
     * @return JsonResponse
     */
    public function projectRules(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $this->disableQueryRoleCheck = true;
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->getQuery()
        );
        $this->disableQueryRoleCheck = false;

        $itemsQuery->with('rules');

        $items = [];
        $actionList = Rule::getActionList();
        $user = $request->user();

        foreach ($user->projectsRelation as $relation) {
            /** @var Role $role */
            $role = $relation->role;
            /** @var Rule[] $rules */
            $rules = $role->rules;

            foreach ($rules as $rule) {
                if (!$rule->allow) {
                    continue;
                }

                $name = $actionList[$rule->object][$rule->action] ?? "$rule->object/$rule->action";
                if (!array_key_exists($name, $items)) {
                    $items[$relation->project_id][$name] = [
                        'object' => $rule->object,
                        'action' => $rule->action,
                        'name' => $name,
                    ];
                }
            }
        }

        return response()->json([ 'success' => true, 'res' => Filter::process(
            $this->getEventUniqueName('answer.success.item.project-rules'),
            $items
        )]);
    }

    /**
     * @api             {post} /v1/roles/attach-user Attach User
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
     * @apiSuccess {Boolean}   success  Indicates successful request when `TRUE`
     * @apiSuccess {Object[]}  res      Relations
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @param Request $request
     * @return JsonResponse
     * @codeCoverageIgnore until it is implemented on frontend
     */
    public function attachToUser(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'relations.*.user_id' => 'integer|exists:users,id',
            'relations.*.role_id' => 'integer|exists:role,id',
        ]);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $relations = $request->post('relations');
        foreach ($relations as $relation) {
            $user_id = $relation['user_id'];
            $role_id = $relation['role_id'];

            /** @var Role $role */
            $role = Role::query()->find($role_id);
            if (!$role) {
                continue;
            }

            $role->attachToUser($user_id);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'success' => true,
                'res' => $relations,
            ])
        );
    }

    /**
     * @api             {post} /v1/roles/detach-user Detach User
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
     * @apiSuccess {Boolean}   success  Indicates successful request when `TRUE`
     * @apiSuccess {Object[]}  res      Relations
     *
     * @apiUse RoleObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
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
     * @param Request $request
     * @return JsonResponse
     * @codeCoverageIgnore until it is implemented on frontend
     */
    public function detachFromUser(Request $request): JsonResponse
    {
        $validator = \Validator::make($request->all(), [
            'relations.*.user_id' => 'integer|exists:users,id',
            'relations.*.role_id' => 'integer|exists:role,id',
        ]);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $relations = $request->post('relations');
        foreach ($relations as $relation) {
            $user_id = $relation['user_id'];
            $role_id = $relation['role_id'];

            /** @var Role $role */
            $role = Role::query()->find($role_id);
            if (!$role) {
                continue;
            }

            $role->detachFromUser($user_id);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'success' => true,
                'res' => $relations,
            ])
        );
    }

    /**
     * @param  bool  $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $user = Auth::user();
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = Role::can($user, 'roles', 'full_access');

        if ($full_access || $this->disableQueryRoleCheck) {
            return $query;
        }

        $query->where(['id' => $user->role_id]);

        return $query;
    }
}
