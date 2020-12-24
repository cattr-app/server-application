<?php

namespace App\Http\Requests\Task;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Models\Task;
use App\Http\Requests\FormRequest;

class ShowTaskRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', Task::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|int',
        ];
    }
}
