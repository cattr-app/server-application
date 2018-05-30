<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\models\Rule;
use Auth;

/**
 * Class AllowedController
 *
 * @package App\Http\Controllers\Api\v1
 */
class AllowedController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Project::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'allowed';
    }

    /**
     * @api {get|post} /api/v1/allowed/list List
     * @apiDescription Get allowed action list
     * @apiVersion 0.1.0
     * @apiName GetAllowedActionList
     * @apiGroup Allowed
     *
     * @apiParam {Integer} [rule_id] Rule's Role's ID
     *
     * @apiSuccess {Object[]}  rules             Array of Rule objects
     * @apiSuccess {Object}    rules.rule        Rule object
     * @apiSuccess {String}    rules.rule.object Object of rule
     * @apiSuccess {String}    rules.rule.action Action of rule
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $roleId = Filter::process($this->getEventUniqueName('request.item.edit'), (int) $request->get('role_id'));
        $actionList = Rule::getActionList();

        $items = [];

        if ($roleId > 0) {
            $rules = Role::find($roleId)->rules;

            if (!$rules) {
                return response()->json(Filter::process(
                    $this->getEventUniqueName('answer.error.item.list'),
                    [
                        'error' => 'rules not found',
                    ]),
                    400
                );
            }

        } else {
            /** @var Rule[] $rules */
            $rules = Auth::user()->role->rules;
        }

        foreach ($rules as $rule) {
            if (!$rule->allow) {
                continue;
            }

            $items[] = [
                'object' => $rule->object,
                'action' => $rule->action,
            ];
        }

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $items
        ));
    }

}
