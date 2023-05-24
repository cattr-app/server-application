<?php

namespace App\Http\Requests\User;

use App\Enums\Role;
use App\Models\User;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EditUserRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('update', User::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int',
            'full_name' => 'sometimes|required|string',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore(Request::input('id'))
            ],
            'user_language' => 'sometimes|required',
            'password' => 'sometimes|required|min:6',
            'important' => 'sometimes|bool',
            'active' => 'sometimes|required|bool',
            'screenshots_active' => 'sometimes|required|bool',
            'manual_time' => 'sometimes|required|bool',
            'screenshots_interval' => 'sometimes|required|int|min:1|max:15',
            'computer_time_popup' => 'sometimes|required|int|min:1',
            'timezone' => 'sometimes|required|string',
            'role_id' => ['sometimes', 'required', new Enum(Role::class)],
            'project_roles' => 'sometimes|present|array',
            'project_roles.*.projects_ids.*' => 'required|array',
            'projects_roles.*.project_ids.*.id' => 'required|int|exists:projects,id',
            'project_roles.*.role_id' => ['required', new Enum(Role::class)],
            'type' => 'sometimes|required|string',
            'web_and_app_monitoring' => 'sometimes|required|bool',
        ];
    }
}
