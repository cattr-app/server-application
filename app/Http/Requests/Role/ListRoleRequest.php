<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\QueryHelperTrait;
use App\Models\Role;
use App\Http\Requests\CattrFormRequest;

class ListRoleRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;
    use QueryHelperTrait;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', Role::class);
    }

    public function _rules(): array
    {
        return $this->helperRules();
    }
}
