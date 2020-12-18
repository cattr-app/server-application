<?php

namespace App\Http\Requests\ProjectMember;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\FormRequest;
use App\Models\Project;

class BulkEditProjectMemberRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return $this->user()->can('updateMembers', Project::find(request('project_id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|int|exists:projects,id',
            'user_roles' => 'present|array',
            'user_roles.*.user_id' => 'required|distinct|int|exists:users,id',
            'user_roles.*.role_id' => 'required|int|exists:role,id',
        ];
    }
}
