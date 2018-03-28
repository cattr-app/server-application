<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use Filter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\models\Rule;

/**
 * Class ActionsController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ActionsController extends ItemController
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
        return 'action';
    }

    /**
     * Get action list.
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function list(Request $request): JsonResponse
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
            $this->getEventUniqueName('answer.success.item.list'), $items
        ));
    }

}
