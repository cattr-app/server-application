<?php

namespace App\Http\Controllers\Api\v1;

use Filter;
use Illuminate\Http\Request;
use App\models\Rule;


class ActionsController extends ItemController
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
        return 'action';
    }

    /**
     * Get action list.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function list(Request $request)
    {
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

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }


}
