<?php

namespace App\Http\Requests\User;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\User;
use App\Http\Requests\CattrFormRequest;

class ListUsersRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('view', User::class);
    }

    protected function _rules(): array
    {
        return [];
    }
}
