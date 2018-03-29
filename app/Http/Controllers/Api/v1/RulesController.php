<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Rule;
use Filter;
use Auth;
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
            'role_id'        => 'required',
            'object'         => 'required',
            'action'         => 'required',
            'allow'          => 'required',
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
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.edit'), $request->all());

        $cls = $this->getItemClass();

        Role::updateRules();

        $rule = Rule::where([
            'role_id' => $requestData['role_id'],
            'object' => $requestData['object'],
            'action' => $requestData['action'],
        ])->first();

        if (!$rule) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'rule does not exist'
                ]),
                400
            );
        }

        if (Auth::user()->role_id === $rule->role_id) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'you cannot change your own privileges'
                ]),
                403
            );
        }

        $rule->allow = $requestData['allow'];

        $rule->save();

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.edit'), [
                'message' => 'OK',
            ]
        ));
    }
}
