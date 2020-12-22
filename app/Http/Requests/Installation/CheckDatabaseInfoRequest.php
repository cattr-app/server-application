<?php

namespace App\Http\Requests\Installation;

use App\Http\Requests\FormRequest;

class CheckDatabaseInfoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'host' => 'required|string',
            'database' => 'required|string',
            'user' => 'required|string',
            'password' => 'required|string',
        ];
    }
}
