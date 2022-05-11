<?php

namespace App\Http\Requests\Priority;

use App\Http\Requests\CattrFormRequest;
use App\Models\Priority;

class DestroyPriorityRequest extends CattrFormRequest
{
    public function _authorize(): bool
    {
        return $this->user()->can('destroy', Priority::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|integer|exists:priorities,id',
        ];
    }
}
