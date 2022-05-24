<?php

namespace App\Http\Requests\Interval;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\TimeInterval;
use App\Http\Requests\CattrFormRequest;

class ListIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('viewAny', TimeInterval::class);
    }

    public function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
