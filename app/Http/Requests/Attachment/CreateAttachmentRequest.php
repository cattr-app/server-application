<?php

namespace App\Http\Requests\Attachment;

use App\Helpers\AttachmentHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;

class CreateAttachmentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return true;
    }

    public function _rules(): array
    {
        // The size rule takes kilobytes, the helper returns bytes
        $maxFileSize = AttachmentHelper::getMaxAllowedFileSize() / 1024;
        return [
            'attachment' => "file|required|max:$maxFileSize",
        ];
    }
}
