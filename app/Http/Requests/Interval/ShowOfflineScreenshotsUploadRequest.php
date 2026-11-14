<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\CattrFormRequest;

class ShowOfflineScreenshotsUploadRequest extends CattrFormRequest
{
    /**
     * Any authenticated user: the controller answers 404 for an upload of another user, like for a missing one.
     */
    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        return [];
    }
}
