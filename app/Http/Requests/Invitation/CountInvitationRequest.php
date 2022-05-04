<?php

namespace App\Http\Requests\Invitation;

use App\Http\Requests\CattrFormRequest;
use App\Models\Invitation;

class CountInvitationRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('view', Invitation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [];
    }
}
