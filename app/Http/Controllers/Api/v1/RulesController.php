<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Rule;
use Filter;
use Auth;
use League\Flysystem\Exception;
use Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class RulesController
 *
 * @package App\Http\Controllers\Api\v1
 */
class RulesController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Rule::class;
    }

    /**
     * @return array
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
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'rule';
    }

    /**
     * @return array
     */
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

    /**
     * @apiDefine RuleRelations
     *
     * @apiParam {String} [with]              For add relation model in response
     * @apiParam {Object} [role] `QueryParam` Rules's relation role. All params in <a href="#api-Roles-GetRolesList" >@Role</a>
     */

    /**
     * @apiDefine RuleRelationsExample
     * @apiParamExample {json} Request-With-Relations-Example:
     *  {
     *      "with":      "role",
     *      "role.name": ["like", "%lorem%"]
     *  }
     */

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Throwable
     * @api {post} /api/v1/rules/edit Edit
     * @apiDescription Edit Rule
     * @apiVersion 0.1.0
     * @apiName EditRule
     * @apiGroup Rule
     *
     * @apiParam {Integer} role_id Role id
     * @apiParam {String}  object  Object name
     * @apiParam {String}  action  Action name
     * @apiParam {Boolean} allow   Allow status
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "role_id": 2,
     *      "object": "projects",
     *      "action": "create",
     *      "allow": 1
     *  }
     *
     * @apiSuccess {String} message OK
     *
     * @apiUse DefaultEditErrorResponse
     * @apiUse UnauthorizedError
     *
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
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }


        if (!Role::updateAllow($requestData['role_id'], $requestData['object'], $requestData['action'], $requestData['allow'])) {
            return response()->json([
                'success' => false,
                'error_type' => 'query.item_not_found',
                'message' => 'Rule does not exist'
            ], 404);
        }
        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.edit'),
            [
                'success' => true,
                'message' => 'Role successfully updated',
            ]
        ));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Throwable
     * @api {post} /api/v1/rules/bulk-edit bulkEdit
     * @apiDescription Editing Multiple Rules
     * @apiVersion 0.1.0
     * @apiName bulkEditRules
     * @apiGroup Rule
     *
     * @apiParam {Object[]} rules                Rules
     * @apiParam {Object}   rules.object         Rule
     * @apiParam {Integer}  rules.object.role_id Role id
     * @apiParam {String}   rules.object.object  Rule object name
     * @apiParam {String}   rules.object.action  Rule action name
     * @apiParam {Boolean}  rules.object.allow   Rule allow status
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "rules":
     *      [
     *          {
     *              "role_id": 2,
     *              "object": "projects",
     *              "action": "create",
     *              "allow": 0
     *          },
     *          {
     *              "role_id": 2,
     *              "object": "projects",
     *              "action": "list",
     *              "allow": 0
     *          }
     *      ]
     *  }
     *
     * @apiSuccess {String[]} messages         Messages
     * @apiSuccess {String}   messages.message OK
     *
     * @apiSuccessExample {json} Response example
     * {
     * timeInterval,timeInterval.task
     * }
     *
     * @apiUse DefaultEditErrorResponse
     * @apiUse UnauthorizedError
     *
     */
    public function bulkEdit(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.bulkEdit'), $request->all());
        $result = [];
        Role::updateRules();

        if (empty($requestData['rules'])) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'rules is empty',
                ]),
                400
            );
        }

        $rules = $requestData['rules'];
        if (!is_array($rules)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'rules should be an array',
                ]),
                400
            );
        }

        foreach ($rules as $rule) {
            $validator = Validator::make(
                $rule,
                Filter::process($this->getEventUniqueName('validation.item.edit'), $this->getValidationRules())
            );

            if ($validator->fails()) {
                $result[] = [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors(),
                    'code' => 400
                ];
                continue;
            }

            if (Role::updateAllow($rule['role_id'], $rule['object'], $rule['action'], $rule['allow'])) {
                $result[] = ['message' => 'OK'];
            };
        }

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.bulkEdit'),
            [
                'messages' => $result,
            ]
        ));
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @api {get} /api/v1/rules/actions Actions
     * @apiDescription Get list of Rules Actions
     * @apiVersion 0.1.0
     * @apiName GetRulesActions
     * @apiGroup Rule
     *
     * @apiSuccessExample {json} Response example
     * [
     *   {
     *     "object": "projects",
     *     "action": "list",
     *     "name": "Project list"
     *   },
     *   {
     *     "object": "projects",
     *     "action": "create",
     *     "name": "Project create"
     *   },
     *   {
     *     "object": "projects",
     *     "action": "show",
     *     "name": "Project show"
     *   }
     * ]
     *
     * @apiSuccess (200) {Object[]} actions               Actions
     * @apiSuccess (200) {Object}   actions.action        Applied to
     * @apiSuccess (200) {String}   actions.action.object Applied action
     * @apiSuccess (200) {String}   actions.action.action Action type
     * @apiSuccess (200) {String}   actions.action.string Action name
     *
     * @apiUse UnauthorizedError
     *
     */
    public function actions(Request $request): JsonResponse
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

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $items
        ));
    }
}
