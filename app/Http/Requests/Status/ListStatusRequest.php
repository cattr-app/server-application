<?php

namespace App\Http\Requests\Status;

use App\Http\Requests\CattrFormRequest;
use App\Models\Status;

class ListStatusRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('viewAny', Status::class);
    }

    public function _rules(): array
    {
        return [];
    }
}
