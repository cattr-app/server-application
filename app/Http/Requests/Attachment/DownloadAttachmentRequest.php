<?php

namespace App\Http\Requests\Attachment;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;

class DownloadAttachmentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
//      TODO: write following rules
//        [ ] when user can view Attachment`s parent
        return true;
    }

    public function _rules(): array
    {
        return [];
    }
}
