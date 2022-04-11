<?php

namespace App\Http\Requests;

use App\Exceptions\Entities\AuthorizationException;
use Filter;
use Illuminate\Foundation\Http\FormRequest;

abstract class CattrFormRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization(): void
    {
        throw new AuthorizationException(AuthorizationException::ERROR_TYPE_FORBIDDEN);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return Filter::process(Filter::getValidationFilterName(), $this->_rules());
    }

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Filter::process(Filter::getAuthFilterName(), $this->_authorize());
    }

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    abstract protected function _authorize(): bool;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract protected function _rules(): array;
}
