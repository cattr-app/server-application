<?php

namespace App\Http\Requests\Task;

use App\Helpers\QueryHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rule;

class CalendarRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return true;
    }

    public function _rules(): array
    {
        return array_merge(QueryHelper::getValidationRules(), [
            'start_at' => ['required', 'date', Rule::when($this->input('due_date'), 'before_or_equal:end_at')],
            'end_at' => ['required', 'date', Rule::when($this->input('start_date'), 'after_or_equal:start_at')],
        ]);
    }
}
