<?php

namespace App\Http\Requests\Role;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Role;
use App\Http\Requests\CattrFormRequest;

class ListRoleRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('viewAny', Role::class);
    }

    public function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
