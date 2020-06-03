<?php

namespace App\Http\Controllers\Api\v1;

use Filter;
use App\Models\Role;
use App\Models\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;
use Throwable;

/**
 * Class RulesController
 */
class RulesController extends ItemController
{
    public static function getControllerRules(): array
    {
        return [
            'index' => 'rules.list',
            'count' => 'rules.list',
            'edit' => 'rules.edit',
            'bulkEdit' => 'rules.bulk-edit',
            'actions' => 'rules.actions',
        ];
    }

    public function getItemClass(): string
    {
        return Rule::class;
    }

    public function getEventUniqueNamePart(): string
    {
        return 'rule';
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.edit'), $request->all());
        Role::updateRules();

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.edit'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }


        if (!Role::updateAllow(
            $requestData['role_id'],
            $requestData['object'],
            $requestData['action'],
            $requestData['allow']
        )) {
            return new JsonResponse([
                'success' => false,
                'error_type' => 'query.item_not_found',
                'message' => 'Rule does not exist'
            ], 404);
        }
        return new JsonResponse(Filter::process(
            $this->getEventUniqueName('answer.success.item.edit'),
            [
                'success' => true,
                'message' => 'Role successfully updated',
            ]
        ));
    }

    /**
     * @api             {get, post} /v1/rules/list List
     * @apiDescription  Get list of Rules
     *
     * @apiVersion      1.0.0
     * @apiName         GetRulesList
     * @apiGroup        Rule
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {Integer}  [id]          ID
     * @apiParam {Integer}  [role_id]     ID of the role
     * @apiParam {String}   [object]      Object with what rule works
     * @apiParam {String}   [action]      Action of the rule
     * @apiParam {Integer}  [allow]       ID of the role
     * @apiParam {String}   [created_at]  Creation DateTime
     * @apiParam {String}   [updated_at]  Update DateTime
     * @apiParam {String}   [deleted_at]  Delete DateTime
     *
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "id": 1
     *  }
     *
     * @apiSuccess {Integer}  id          ID
     * @apiSuccess {Integer}  role_id     ID of the role
     * @apiSuccess {String}   object      Object with what rule works
     * @apiSuccess {String}   action      Action of the rule
     * @apiSuccess {Integer}  allow       ID of the role
     * @apiSuccess {String}   created_at  Creation DateTime
     * @apiSuccess {String}   updated_at  Update DateTime
     * @apiSuccess {String}   deleted_at  Delete DateTime or `NULL` if user wasn't deleted
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 37,
     *      "role_id": "1",
     *      "object": "register",
     *      "action": "create",
     *      "allow": "1",
     *      "created_at": "2020-01-23T09:42:24+00:00",
     *      "updated_at": "2020-01-23T09:42:24+00:00",
     *      "deleted_at": null
     *    }
     *  ]
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @api             {get,post} /v1/rules/count Count
     * @apiDescription  Count Rules
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Rule
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of rules that we have
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
     * @api {post} /v1/rules/edit Edit
     * @apiDescription Edit Rule
     *
     * @apiVersion 1.0.0
     * @apiName EditRule
     * @apiGroup Rule
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   rules_edit
     * @apiPermission   rules_full_access
     *
     * @apiParam {Integer} role_id Role id
     * @apiParam {String}  object  Object name
     * @apiParam {String}  action  Action name
     * @apiParam {Boolean} allow   Allow status (`1` - allow)
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "role_id": 2,
     *      "object": "projects",
     *      "action": "create",
     *      "allow": 1
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Message from server
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Role successfully updated"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */

    public function getValidationRules(): array
    {
        return [
            'role_id' => 'required',
            'object' => 'required',
            'action' => 'required',
            'allow' => 'required',
        ];
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/rules/bulk-edit Bulk Edit
     * @apiDescription  Editing Multiple Rules
     *
     * @apiVersion      1.0.0
     * @apiName         bulkEditRules
     * @apiGroup        Rule
     *
     * @apiPermission   rules_bulk_edit
     * @apiPermission   rules_full_access
     */

    /**
     * @api {get, post} /v1/rules/actions Actions
     * @apiDescription Get list of Rules Actions
     *
     * @apiVersion 1.0.0
     * @apiName GetRulesActions
     * @apiGroup Rule
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   rules_actions
     * @apiPermission   rules_full_access
     *
     * @apiSuccess {Boolean}   success     Indicates successful request when `TRUE`
     * @apiSuccess {Object[]}  res         Available actions
     * @apiSuccess {String}    res.object  Object with what action works
     * @apiSuccess {String}    res.action  Action type
     * @apiSuccess {String}    res.name    Action name
     *
     * @apiSuccessExample {json} Response example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": [
     *      {
     *       "object": "projects",
     *       "action": "list",
     *       "name": "Project list"
     *      },
     *      {
     *        "object": "projects",
     *        "action": "create",
     *        "name": "Project create"
     *      },
     *      {
     *        "object": "projects",
     *        "action": "show",
     *        "name": "Project show"
     *      }
     *    ]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    public function actions(): JsonResponse
    {
        /** @var array[] $actionList */
        $actionList = Rule::getActionList();

        $items = [];

        foreach ($actionList as $object => $actions) {
            foreach ($actions as $action => $name) {
                $items[] = [
                    'object' => $object,
                    'action' => $action,
                    'name' => $name,
                ];
            }
        }

        return new JsonResponse([
            'success' => true,
            'res' => Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                $items
            )
        ]);
    }
}
