<?php

namespace App\Http\Requests\Project;

use App\Enums\ScreenshotsState;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'screenshots_state' => ['sometimes', 'required', new Enum(ScreenshotsState::class)],
            'statuses' => 'sometimes|array',
            'statuses.*.id' => 'required|exists:statuses,id',
            'statuses.*.color' => 'sometimes|nullable|string|regex:/^#[a-f0-9]{6}$/i',
            'phases' => 'sometimes|array',
            'phases.*.id' => 'sometimes|required|exists:project_phases,id',
            'phases.*.name' => 'required|string|min:1|max:255',
            'group' => Rule::when(!is_array($this->input('group')), 'sometimes|nullable|integer|exists:project_groups,id'),
            'group.id' => Rule::when(is_array($this->input('group')), 'sometimes|nullable|integer|exists:project_groups,id'),
        ];
    }
}
