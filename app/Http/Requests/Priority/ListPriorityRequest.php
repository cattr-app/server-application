<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\QueryHelperTrait;
use App\Models\Priority;
use App\Http\Requests\CattrFormRequest;

class ListPriorityRequest extends CattrFormRequest
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
        return $this->user()->can('view', Priority::class);
    }

    public function _rules(): array
    {
        return $this->helperRules();
    }
}
