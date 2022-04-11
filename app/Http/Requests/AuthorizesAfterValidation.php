<?php

namespace App\Http\Requests;

use App\Helpers\FilterDispatcher;
use Filter;

trait AuthorizesAfterValidation
{
    /**
     * @return bool
     */
    public function _authorize(): bool
    {
        return true;
    }

    /**
     * @param $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $validator->failed() && ! Filter::process(Filter::getAuthValidationFilterName(), $this->authorizeValidated())) {
                $this->failedAuthorization();
            }
        });
    }

    /**
     * @return mixed
     */
    abstract public function authorizeValidated(): mixed;
}
