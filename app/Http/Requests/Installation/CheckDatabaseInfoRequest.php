<?php

namespace App\Http\Requests\Installation;

use App\Http\Requests\CattrFormRequest;

class CheckDatabaseInfoRequest extends CattrFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'db_host' => 'required|string',
            'database' => 'required|string',
            'db_user' => 'required|string',
            'db_password' => 'required|string',
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
