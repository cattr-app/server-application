<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\CattrFormRequest;

class RegistrationRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return true;
    }

    public function _rules(): array
    {
        return [
            'email' => 'required|email',
            'full_name' => 'required|string',
            'password' => 'required|string|min:6',
        ];
    }
}
