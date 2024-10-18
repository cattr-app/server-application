<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Str;

class UploadOfflineIntervalsRequest extends CattrFormRequest
{

    public function _authorize(): bool
    {
        return auth()->check();
    }

    public function _rules(): array
    {
        return [
            'file' => [
                'required',
                File::types('application/zip')->max(12 * 1024),
                function ($_, UploadedFile $file, $fail) {
                    $fileName = $file->getClientOriginalName();
                    if (Str::endsWith($fileName, '.cattr') === false) {
                        $fail('validation.offline-sync.wrong_extension')->translate();
                    }
                }
            ],
        ];
    }
}
