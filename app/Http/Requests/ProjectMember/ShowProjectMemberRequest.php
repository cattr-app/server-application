<?php

namespace App\Http\Requests\ProjectMember;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Project;

class ShowProjectMemberRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('updateMembers', Project::find(request('project_id')));
    }

    public function _rules(): array
    {
        return ['project_id' => 'required|int|exists:projects,id'];
    }
}
