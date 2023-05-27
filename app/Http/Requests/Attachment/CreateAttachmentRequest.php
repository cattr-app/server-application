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
//      TODO: write following rules
//        [ ] when user can create Attachments
        return true;
//        return $this->user()->can('create', [Attachment::class, $this->get('project_id')]);
    }

    public function _rules(): array
    {
        $maxFileSize = AttachmentHelper::getMaxAllowedFileSize();
        return [
            'attachment' => "file|required|max:$maxFileSize",
        ];
    }
}
