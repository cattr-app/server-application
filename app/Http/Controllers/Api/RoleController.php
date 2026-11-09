<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use CatEvent;
use Filter;
use Illuminate\Http\JsonResponse;

class RoleController extends ItemController
{
    /**
     * Get Roles List
     *
     * Retrieves the list of roles available in the system.
     *
     * The role `id` is what `role_id` of a user holds: `ANY` is -1, `ADMIN` 0, `MANAGER` 1, `USER` 2, `AUDITOR` 3.
     *
     * @response array{status: int, success: true, data: list<array{name: string, id: int}>}
     */
    public function index(): JsonResponse
    {
        CatEvent::dispatch(Filter::getBeforeActionEventName());

        $items = Filter::process(
            Filter::getActionFilterName(),
            //For compatibility reasons generate serialized model-like array
            array_map(fn ($role) => ['name' => $role->name, 'id' => $role->value], Role::cases()),
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items]);

        return responder()->success($items)->respond();
    }
}
