<?php

namespace App\Http\Requests\Attachment;

use App\Contracts\AttachmentService;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;

class DownloadAttachmentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('view', request('attachment')->project);
    }

    public function _rules(): array
    {
        return [
            // 7 days at most on every disk (the S3 presigning limit)
            'seconds' => 'sometimes|int|min:1|max:' . AttachmentService::MAX_TEMPORARY_URL_SECONDS,
        ];
    }
}
