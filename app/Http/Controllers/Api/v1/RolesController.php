<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use Filter;
use Illuminate\Http\Request;

class RolesController extends ItemController
{
    function getItemClass()
    {
        return Role::class;
    }

    function getValidationRules()
    {
        return [
            'name' => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'role';
    }

    public function index(Request $request)
    {
        $keyword = Filter::process($this->getEventUniqueName('request.item.list'), $request->get('search'));

        $perPage = 25;

        $cls = $this->getItemClass();
        $cls::updateRules();

        $items = $cls::with('rules')->get();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }
}
