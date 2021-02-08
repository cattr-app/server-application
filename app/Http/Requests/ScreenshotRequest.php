<?php

namespace App\Http\Requests;

use App\Models\TimeInterval;

class ScreenshotRequest extends FormRequest
{
    use AuthorizesAfterValidation;

    /**
     * Determine if user authorized to make this request.
     *
     * @return bool
     */
    public function authorizeValidated(): bool
    {
        $timeInterval =  TimeInterval::find($this->route('id'));

        abort_unless($timeInterval, 404);

        return $this->user()->can('view', $timeInterval);
    }

    public function rules(): array
    {
        return [];
    }
}
