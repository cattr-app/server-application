<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use Filter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RolesController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Role::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'name' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'role';
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = Filter::process($this->getEventUniqueName('request.item.list'), $request->get('search'));

        $perPage = 25;

        $cls = $this->getItemClass();
        $cls::updateRules();

        $items = $cls::with('rules')->get();

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.list'),
            $items
        ));
    }
}
