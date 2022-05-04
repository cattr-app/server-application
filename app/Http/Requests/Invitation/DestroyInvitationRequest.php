<?php

namespace App\Http\Requests\Invitation;

use App\Http\Requests\CattrFormRequest;

class DestroyInvitationRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return ['id' => 'required|int|exists:invitations,id'];
    }
}
