<?php

namespace App\Http\Requests\Priority;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Priority;
use App\Http\Requests\CattrFormRequest;

class ListPriorityRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('viewAny', Priority::class);
    }

    public function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
