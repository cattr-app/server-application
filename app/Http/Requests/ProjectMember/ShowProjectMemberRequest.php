<?php

namespace App\Http\Requests\ProjectMember;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Project;

class ShowProjectMemberRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', Project::find(request('project_id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return ['project_id' => 'required|int|exists:projects,id'];
    }
}
