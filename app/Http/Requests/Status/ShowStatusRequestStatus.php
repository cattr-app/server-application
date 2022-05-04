<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;

class ShowStatusRequestStatus extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:statuses,id',
        ];
    }
}
