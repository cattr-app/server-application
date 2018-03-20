<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Rule;
use Filter;
use Illuminate\Http\Request;

class RulesController extends ItemController
{
    function getItemClass()
    {
        return Rule::class;
    }

    function getValidationRules()
    {
        return [
            'role_id'        => 'required',
            'object'         => 'required',
            'action'         => 'required',
            'allow'          => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'rule';
    }



    public function edit(Request $request)
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.edit'), $request->all());

        $cls = $this->getItemClass();

        Role::updateRules();


        $rule = Rule::where([
            'role_id' => $requestData['role_id'],
            'object' => $requestData['object'],
            'action' => $requestData['action'],
        ])->first();


        if(!$rule)
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), ['error' => 'rule does not exist']),
                400
            );

        $rule->allow = $requestData['allow'];

        $rule->save();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), ['message' => 'OK']),
            200
        );
    }
}
