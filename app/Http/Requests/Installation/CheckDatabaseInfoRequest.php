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
            'db_host' => 'required|string',
            'database' => 'required|string',
            'db_user' => 'required|string',
            'db_password' => 'required|string',
        ];
    }
}
