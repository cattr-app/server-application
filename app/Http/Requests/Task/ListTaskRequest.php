<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\QueryHelperTrait;
use App\Models\Task;
use App\Http\Requests\CattrFormRequest;

class ListTaskRequest extends CattrFormRequest
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
        return $this->user()->can('view', Task::class);
    }

    public function _rules(): array
    {
        return $this->helperRules();
    }
}
