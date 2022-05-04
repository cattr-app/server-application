<?php

namespace App\Http\Requests\User;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\User;
use App\Http\Requests\CattrFormRequest;

class ShowUserRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', User::find(request('id')));
    }

    protected function _rules(): array
    {
        return [
            'id' => 'required|int',
        ];
    }
}
