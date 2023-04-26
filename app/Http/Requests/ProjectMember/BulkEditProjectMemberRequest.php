<?php

namespace App\Http\Requests\ProjectMember;

use App\Enums\Role;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Project;
use Illuminate\Validation\Rules\Enum;

class BulkEditProjectMemberRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('updateMembers', Project::find(request('project_id')));
    }

    public function _rules(): array
    {
        return [
            'project_id' => 'required|int|exists:projects,id',
            'user_roles' => 'present|array',
            'user_roles.*.user_id' => 'required|distinct|int|exists:users,id',
            'user_roles.*.role_id' => ['required', new Enum(Role::class)],
        ];
    }
}
