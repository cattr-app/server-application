<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\FormRequest;
use App\Models\Project;

class EditProjectRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Project::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id' => 'required|int|exists:projects,id',
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'default_priority_id' => 'sometimes|integer|exists:priorities,id',
            'statuses' => 'sometimes|array',
            'statuses.*.id' => 'required|exists:statuses,id',
            'statuses.*.color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
        ];
    }
}
