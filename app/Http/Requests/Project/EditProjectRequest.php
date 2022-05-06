<?php

namespace App\Http\Requests\Project;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Project;

class EditProjectRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Project::find(request('id')));
    }

    public function _rules(): array
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
