<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use Event;
use Filter;
use Illuminate\Http\JsonResponse;

class RoleController extends ItemController
{
    public function index(): JsonResponse
    {
        Event::dispatch(Filter::getBeforeActionEventName());

        $items = Filter::process(
            Filter::getActionFilterName(),
            //For compatibility reasons generate serialized model-like array
            array_map(fn ($role) => ['name' => $role->name, 'id' => $role->value], Role::cases()),
        );

        Event::dispatch(Filter::getAfterActionEventName(), [$items]);

        return responder()->success($items)->respond();
    }
}
