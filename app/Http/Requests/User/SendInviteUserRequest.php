<?php

namespace App\Http\Requests\User;

use App\Http\Requests\CattrFormRequest;
use App\Models\User;

class SendInviteUserRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:users,id'
        ];
    }
}
