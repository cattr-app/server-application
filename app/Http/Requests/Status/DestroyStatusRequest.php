<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;
use App\Models\User;

class DestroyStatusRequest extends CattrFormRequest
{
    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function _authorize(): bool
    {
        return $this->user()->can('delete', Status::class);
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
