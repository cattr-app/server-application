<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;
use App\Scopes\TimeIntervalAccessScope;

class PutScreenshotRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    private ?TimeInterval $timeInterval = null;

    public function authorizeValidated(): bool
    {
        $interval = $this->timeInterval();

        return $this->user()->can(
            'create',
            [
                TimeInterval::class,
                $interval->user_id,
                $interval->task_id,
                false,
            ],
        );
    }

    public function timeInterval(): TimeInterval
    {
        // Read access and screenshot upload permissions differ: return 403 for an unauthorized upload.
        return $this->timeInterval ??= TimeInterval::withoutGlobalScope(TimeIntervalAccessScope::class)
            ->findOrFail($this->route('interval'));
    }

    public function _rules(): array
    {
        return [
            'screenshot' => 'required|image',
        ];
    }
}
