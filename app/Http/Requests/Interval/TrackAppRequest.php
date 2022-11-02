<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

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
            'title' => 'required|string',
            'executable' => 'required|string',
        ];
    }
}
