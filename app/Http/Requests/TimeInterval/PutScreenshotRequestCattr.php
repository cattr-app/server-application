<?php

namespace App\Http\Requests\TimeInterval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class PutScreenshotRequestCattr extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        return true;
    }

    public function _rules(): array
    {
        return [
            'screenshot' => 'required|image',
        ];
    }
}
