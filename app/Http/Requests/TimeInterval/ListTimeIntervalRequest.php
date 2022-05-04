<?php

namespace App\Http\Requests\TimeInterval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\QueryHelperTrait;
use App\Models\TimeInterval;
use App\Http\Requests\CattrFormRequest;

class ListTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;
    use QueryHelperTrait;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', TimeInterval::class);
    }

    public function _rules(): array
    {
        return $this->helperRules();
    }
}
