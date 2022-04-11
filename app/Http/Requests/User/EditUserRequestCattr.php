<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Presenters\User\OrdinaryUserPresenter;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditUserRequestCattr extends CattrFormRequest
{
    /**
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    public function _authorize(): bool
    {
        if (auth()->check() && auth()->user()?->hasRole('admin')) {
            return true;
        }

        if (!auth()->user()?->can('update', User::find(request('id')))) {
            return false;
        }

        $fillableFields = app()->make(OrdinaryUserPresenter::class)->getFillable();
        $requestFields = array_keys($this->except('id'));

        $fieldsDiff = array_diff($requestFields, $fillableFields);

        if (count($fieldsDiff) > 0) {
            $errorMessages = [];

            foreach ($fieldsDiff as $fieldKey) {
                $errorMessages[$fieldKey] = __('You don\'t have permission to edit this field');
            }

            throw ValidationException::withMessages($errorMessages);
        }

        return true;
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
            'role_id' => 'sometimes|required|int|exists:role,id',
            'project_roles' => 'sometimes|present|array',
            'project_roles.*.projects_ids.*' => 'required|array',
            'projects_roles.*.project_ids.*.id' => 'required|int|exists:projects,id',
            'project_roles.*.role_id' => 'required|int|exists:role,id',
            'type' => 'sometimes|required|string',
            'web_and_app_monitoring' => 'sometimes|required|bool',
        ];
    }
}
