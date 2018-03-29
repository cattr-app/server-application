<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
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
     * Get allowed action list.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function list(Request $request): JsonResponse
    {
        $actionList = Rule::getActionList();

        $items = [];

        /** @var Rule[] $rules */
        $rules = Auth::user()->role->rules;

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
