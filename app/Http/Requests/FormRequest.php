<?php

namespace App\Http\Requests;

use App\Exceptions\Entities\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;

abstract class FormRequest extends BaseFormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_FORBIDDEN);
    }
}
