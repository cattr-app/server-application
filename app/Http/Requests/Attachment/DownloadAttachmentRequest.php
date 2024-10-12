<?php

namespace App\Http\Requests\Attachment;

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
            'seconds' => 'sometimes|int'
        ];
    }
}
