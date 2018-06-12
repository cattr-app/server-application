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
     * @api {post} /api/v1/roles/list List
     * @apiDescription Get list of Roles
     * @apiVersion 0.1.0
     * @apiName GetRolesList
     * @apiGroup Roles
     *
     * @apiParam {Integer}  [id]          `QueryParam` Role ID
     * @apiParam {Integer}  [user_id]     `QueryParam` Role's Users ID
     * @apiParam {String}   [name]        `QueryParam` Role Name
     * @apiParam {DateTime} [created_at]  `QueryParam` Role Creation DateTime
     * @apiParam {DateTime} [updated_at]  `QueryParam` Last Role update DataTime
     * @apiParam {DateTime} [deleted_at]  `QueryParam` When Role was deleted (null if not)
     *
     * @apiSuccess (200) {Role[]} Array Array of Role objects
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $cls = $this->getItemClass();
        $cls::updateRules();

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
     */

    /**
     * @api {post} /api/v1/roles/show Show
     * @apiDescription Show Role
     * @apiVersion 0.1.0
     * @apiName ShowRole
     * @apiGroup Role
     */

    /**
     * @api {post} /api/v1/roles/edit Edit
     * @apiDescription Edit Role
     * @apiVersion 0.1.0
     * @apiName EditRole
     * @apiGroup Role
     */

    /**
     * @api {post} /api/v1/roles/destroy Destroy
     * @apiDescription Destroy Role
     * @apiVersion 0.1.0
     * @apiName DestroyRole
     * @apiGroup Role
     */

    /**
     * @api {post} /api/v1/roles/allowed-rules AllowedRules
     * @apiDescription Get Rule's allowed action list
     * @apiVersion 0.1.0
     * @apiName GetRulesAllowedActionList
     * @apiGroup Roles
     *
     * @apiParam {Integer} id Role's ID
     *
     * @apiSuccess {Object[]} array               Array of Rule objects
     * @apiSuccess {Object}   array.object        Rule object
     * @apiSuccess {String}   array.object.object Object of rule
     * @apiSuccess {String}   array.object.action Action of rule
     * @apiSuccess {String}   array.object.name   Name of rule
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function allowedRules(Request $request): JsonResponse
    {
        $roleId = Filter::process($this->getEventUniqueName('request.item.allowed-rules'), $request->get('id'));
        $isInt = is_int($roleId);
        $items = [];
        /** @var array[] $actionList */
        $actionList = Rule::getActionList();

        if ($roleId <= 0 || !$isInt) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.allowed-rules'),
                [
                    'error' => 'Validation fail',
                    'reason' => 'Invalid id',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->getQuery()
        );
        $role = $itemsQuery->find($roleId);

        if (!$role) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.allowed-rules'),
                [
                    'error' => 'Role not found',
                ]),
                400
            );
        }

        /** @var Rule[] $rules */
        $rules = $role->rules;

        foreach ($rules as $rule) {
            if (!$rule->allow) {
                continue;
            }

            $items[] = [
                'object' => $rule->object,
                'action' => $rule->action,
                'name'   => $actionList[$rule->object][$rule->action]
            ];
        }

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.allowed-rules'),
            $items
        ));
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
        $relations_access = Role::can(Auth::user(), 'users', 'relations');

        if ($full_access) {
            return $query;
        }

        $user_role_id = collect(Auth::user()->role_id);

        if ($relations_access) {
            $attached_users_role_id = collect(Auth::user()->attached_users)->flatMap(function($user) {
                return collect($user->role_id);
            });
            $roles_id = collect([$user_role_id, $attached_users_role_id])->collapse()->unique();
            $query->whereIn('id', $roles_id);
        } else {
            $query->whereIn('id', $user_role_id);
        }

        return $query;
    }
}
