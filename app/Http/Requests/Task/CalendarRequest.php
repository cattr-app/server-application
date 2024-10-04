<?php

namespace App\Http\Requests\Task;

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
        return [
            'project_id' => ['sometimes', Rule::when(!is_array($this->input('project_id')), 'nullable|integer|exists:projects,id')],
            'project_id.*' => [Rule::when(is_array($this->input('project_id')), 'integer|exists:projects,id')],
            'start_at' => 'required|date|before_or_equal:end_at',
            'end_at' => 'required|date|after_or_equal:start_at',
        ];
    }
}
