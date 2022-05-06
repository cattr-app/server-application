<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;

class ShowStatusRequestStatus extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('view', Status::class);
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:statuses,id',
        ];
    }
}
