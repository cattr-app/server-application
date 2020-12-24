<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

class SaveSetupRequest extends FormRequest
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
            'captcha_enabled' => 'required|boolean',
            'email' => 'required|email',
            'password' => 'required|string',
            'timezone' => 'required|string',
            'language' => 'required|string',
            'secret_key' => 'nullable|string',
            'site_key' => 'nullable|string',
            'origin' => 'required|string'
        ];
    }
}
