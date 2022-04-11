<?php

namespace App\Http\Requests\Installation;

use App\Http\Requests\CattrFormRequest;

class SaveSetupRequest extends CattrFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'db_host' => 'sometimes|required|string',
            'database' => 'sometimes|required|string',
            'db_user' => 'sometimes|required|string',
            'db_password' => 'sometimes|required|string',
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

    /**
     * @inheritDoc
     */
    protected function _authorize(): bool
    {
        return true;
    }
}
