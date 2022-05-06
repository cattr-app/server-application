<?php

namespace App\Http\Requests\User;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\User;
use App\Http\Requests\CattrFormRequest;

class DestroyUserRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('destroy', User::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int',
        ];
    }
}
