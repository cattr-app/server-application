<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\QueryHelperTrait;
use App\Models\Project;
use App\Http\Requests\CattrFormRequest;

class ListProjectRequest extends CattrFormRequest
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
        return $this->user()->can('viewAny', Project::class);
    }

    public function _rules(): array
    {
        return $this->helperRules();
    }
}
