<?php

namespace App\Http\Requests\User;

use App\Helpers\QueryHelper;
use App\Models\User;
use App\Http\Requests\CattrFormRequest;

class ListUsersRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    protected function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
