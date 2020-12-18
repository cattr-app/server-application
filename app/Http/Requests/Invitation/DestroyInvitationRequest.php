<?php

namespace App\Http\Requests\Invitation;

use App\Http\Requests\FormRequest;

class DestroyInvitationRequest extends FormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return ['id' => 'required|int|exists:invitations,id'];
    }
}
