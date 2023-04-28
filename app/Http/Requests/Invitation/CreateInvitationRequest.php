<?php

namespace App\Http\Requests\Invitation;

use App\Enums\Role;
use App\Http\Requests\CattrFormRequest;
use App\Models\Invitation;
use Illuminate\Validation\Rules\Enum;

class CreateInvitationRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('create', Invitation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'users' => 'required|array',
            'users.*.email' => 'required|email|unique:users,email|unique:invitations,email',
            'users.*.role_id' => ['required', new Enum(Role::class)],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'users.*.email' => 'Email'
        ];
    }
}
