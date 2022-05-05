<?php

namespace App\Http\Requests\Invitation;

use App\Http\Requests\CattrFormRequest;
use App\Models\Invitation;

class ShowInvitationRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('view', Invitation::find(request('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:invitations,id'
        ];
    }
}
