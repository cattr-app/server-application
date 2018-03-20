<?php

namespace App\Http\Controllers\Api\v1;

use Filter;
use Illuminate\Http\Request;
use App\models\Rule;
use Illuminate\Support\Facades\Auth;


class AllowedController extends ItemController
{


    function getItemClass()
    {
        return Project::class;
    }

    function getValidationRules()
    {
        return [
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'allowed';
    }


    /**
     * Get allowed action list.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function list(Request $request)
    {
        $actionList = Rule::getActionList();

        $items = [];


        $rules = Auth::user()->role->rules;


        foreach ($rules as $rule) {

            if(!$rule->allow) {
                continue;
            }

            $items[] = [
                'object' => $rule->object,
                'action' => $rule->action,
            ];
        }

        $items = ['data' => $items];


        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

}
