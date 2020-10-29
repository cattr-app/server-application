<?php

namespace App\Http\Requests;

trait AuthorizesAfterValidation
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @param $validator
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $validator->failed() && ! $this->authorizeValidated()) {
                $this->failedAuthorization();
            }
        });
    }

    /**
     * @return mixed
     */
    abstract public function authorizeValidated();
}
