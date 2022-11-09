<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;

class TrackAppRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        return [
            'title' => 'nullable|string',
            'executable' => 'required|string',
        ];
    }
}
