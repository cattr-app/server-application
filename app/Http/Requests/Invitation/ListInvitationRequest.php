<?php

namespace App\Http\Requests\Invitation;

use App\Helpers\QueryHelper;
use App\Http\Requests\CattrFormRequest;
use App\Models\Invitation;

class ListInvitationRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('viewAny', Invitation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return QueryHelper::getValidationRules();
    }
}
