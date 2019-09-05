<?php

namespace App\Http\Controllers\Api\v1;

use Auth;
use Filter;
use App\Models\Role;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;

class RolesController extends ItemController
{
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
     * @apiDefine RolesRelations
     *
     * @apiParam {String} [with]               For add relation model in response
     * @apiParam {Object} [users] `QueryParam` Roles's relation users. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [rules] `QueryParam` Roles's relation rules. All params in <a href="#api-Rule-GetRulesActions" >@Rules</a>
     */

    /**
     * @apiDefine RolesRelationsExample
     * @apiParamExample {json} Request With Relations Example
     *  {
     *      "with":               "users,rules,users.tasks",
     *      "users.tasks.id":     [">", 1],
     *      "users.tasks.active": 1,
     *      "users.full_name":    ["like", "%lorem%"]
     *  }
     */

    /**
     * @api {post} /api/v1/roles/list List
     * @apiDescription Get list of Roles
     * @apiVersion 0.1.0
     * @apiName GetRolesList
     * @apiGroup Roles
     *
     * @apiParam {Integer}  [id]          `QueryParam` Role ID
     * @apiParam {Integer}  [user_id]     `QueryParam` Role's Users ID
     * @apiParam {String}   [name]        `QueryParam` Role Name
     * @apiParam {String} [created_at]    `QueryParam` Role Creation DateTime
     * @apiParam {String} [updated_at]    `QueryParam` Last Role update DataTime
     * @apiUse RolesRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          [">", 1]
     *      "user_id":     ["=", [1,2,3]],
     *      "name":        ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     * @apiUse RolesRelationsExample
     *
     * @apiSuccessExample {json} Simple response example
     * [
     *   {
     *     "id": 256,
     *     "name": "test",
     *     "deleted_at": null,
     *     "created_at": "2018-10-12 11:44:08",
     *     "updated_at": "2018-10-12 11:44:08"
     *   }
     * ]
     *
     * @apiSuccess {Object[]} RoleList                  Roles
     * @apiSuccess {Object}   RoleList.Role             Role object
     * @apiSuccess {Integer}  RoleList.Role.id          Role ID
     * @apiSuccess {String}   RoleList.Role.name        Role name
     * @apiSuccess {String}   RoleList.Role.created_at  Role date time of create
     * @apiSuccess {String}   RoleList.Role.updated_at  Role date time of update
     * @apiSuccess {String}   RoleList.Role.deleted_at  Role date time of delete
     * @apiSuccess {Object[]} RoleList.Role.users       Role User
     * @apiSuccess {Object[]} RoleList.Role.rules       Role Task
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     *
     * @return JsonResponse
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
     * @api {post} /api/v1/roles/create Create
     * @apiDescription Create Role
     * @apiVersion 0.1.0
     * @apiName CreateRole
     * @apiGroup Role
     *
     * @apiParam {String} name Roles's name
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "name": "test"
     *  }
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "res": {
     *     "name": "test",
     *     "updated_at": "2018-10-12 11:44:08",
     *     "created_at": "2018-10-12 11:44:08",
     *     "id": 256
     *    }
     * }
     *
     * @apiSuccess {Object}   res             Response object
     * @apiSuccess {Integer}  res.id          Role ID
     * @apiSuccess {String}   res.name        Role name
     * @apiSuccess {String}   res.created_at  Role date time of create
     * @apiSuccess {String}   res.updated_at  Role date time of update
     *
     * @apiUse DefaultCreateErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/roles/show Show
     * @apiDescription Get Role Entity
     * @apiVersion 0.1.0
     * @apiName ShowRole
     * @apiGroup Role
     *
     * @apiParam {Integer}    id                        Role id
     * @apiParam {String}     [name]       `QueryParam` Role Name
     * @apiParam {String}     [created_at] `QueryParam` Role date time of create
     * @apiParam {String}     [updated_at] `QueryParam` Role date time of update
     *
     * @apiUse RolesRelations
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id":          1,
     *      "name":        ["like", "%lorem%"],
     *      "description": ["like", "%lorem%"],
     *      "created_at":  [">", "2019-01-01 00:00:00"],
     *      "updated_at":  ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse RolesRelationsExample
     *
     * @apiSuccess {Object}   Role             Role object
     * @apiSuccess {Integer}  Role.id          Role id
     * @apiSuccess {String}   Role.name        Role name
     * @apiSuccess {String}   Role.created_at  Role date time of create
     * @apiSuccess {String}   Role.updated_at  Role date time of update
     * @apiSuccess {String}   Role.deleted_at  Role date time of delete
     * @apiSuccess {Object[]} Role.users       Role User
     * @apiSuccess {Object[]} Role.rules       Role Task
     *
     * @apiSuccessExample {json} Answer Relations Example
     * {
     *   "id": 1,
     *   "name": "root",
     *   "deleted_at": null,
     *   "created_at": "2018-09-25 06:15:07",
     *   "updated_at": "2018-09-25 06:15:07"
     * }
     *
     * @apiUse DefaultShowErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/roles/edit Edit
     * @apiDescription Edit Role
     * @apiVersion 0.1.0
     * @apiName EditRole
     * @apiGroup Role
     *
     * @apiParam {Integer} id   Role ID
     * @apiParam {String}  name Role Name
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id": 1,
     *      "name": "test"
     *  }
     *
     * @apiSuccess {Object}   Role            Role object
     * @apiSuccess {Integer}  Role.id         Role ID
     * @apiSuccess {String}   Role.name       Role name
     * @apiSuccess {String}   Role.created_at Role date time of create
     * @apiSuccess {String}   Role.updated_at Role date time of update
     * @apiSuccess {String}   Role.deleted_at Role date time of delete
     *
     * @apiUse DefaultEditErrorResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/roles/remove Destroy
     * @apiUse DefaultDestroyRequestExample
     * @apiDescription Destroy Role
     * @apiVersion 0.1.0
     * @apiName DestroyRole
     * @apiGroup Role
     *
     * @apiParam {Integer} id Role id
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id": 1
     *  }
     *
     * @apiUse DefaultDestroyResponse
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/roles/allowed-rules AllowedRules
     * @apiDescription Get Rule allowed action list
     * @apiVersion 0.1.0
     * @apiName GetRulesAllowedActionList
     * @apiGroup Roles
     *
     * @apiParam {Integer} id Role id
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "id": 1
     *  }
     *
     * @apiSuccess {Object[]} array               Rules
     * @apiSuccess {Object}   array.object        Rule
     * @apiSuccess {String}   array.object.object Object of rule
     * @apiSuccess {String}   array.object.action Action of rule
     * @apiSuccess {String}   array.object.name   Name of rule
     *
     * @apiSuccessExample {json} Answer Example
     * [
     *   {
     *     "object": "attached-users",
     *     "action": "bulk-create",
     *     "name": "Attached User relation multiple create"
     *   },
     *   {
     *     "object": "attached-users",
     *     "action": "bulk-remove",
     *     "name": "Attached User relation multiple remove"
     *   }
     * ]
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     * @apiUse UnauthorizedError
     *
     * @apiErrorExample {json} Invalid id Example
     * {
     *   "error": "Validation fail",
     *   "reason": "Invalid id"
     * }
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function allowedRules(Request $request): JsonResponse
    {
        $roleIds = Filter::process($this->getEventUniqueName('request.item.allowed-rules'), $request->get('ids', []));
        if (is_numeric($roleIds)) {
            $roleIds = [$roleIds];
        }

        if (!$roleIds || !is_array($roleIds) || empty($roleIds)) {
            $roleIds = $request->user()->rolesIds();
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->getQuery()
        );

        $roles = $itemsQuery->whereIn('role.id', $roleIds)->get();

        if (!$roles->count()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.allowed-rules'),
                [
                    'error' => 'Roles not found',
                    'reason' => 'Invalid Id'
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

                $items[] = [
                    'object' => $rule->object,
                    'action' => $rule->action,
                    'name' => $actionList[$rule->object][$rule->action]
                ];
            }
        }

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.allowed-rules'),
            $items
        ));
    }

    public function attachToUser(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'relations.*.user_id' => 'integer|exists:users,id',
            'relations.*.role_id' => 'integer|exists:role,id',
        ]);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
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
                'res' => $relations,
            ])
        );
    }

    public function detachFromUser(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'relations.*.user_id' => 'integer|exists:users,id',
            'relations.*.role_id' => 'integer|exists:role,id',
        ]);

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
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
                'res' => $relations,
            ])
        );
    }

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'roles', 'full_access');

        if ($full_access) {
            return $query;
        }

        $user_role_ids = Auth::user()->rolesIds();

        $query->whereIn('id', $user_role_ids);

        return $query;
    }
}
