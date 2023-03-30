<?php

namespace App\Http\Requests\Attachment;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\Attachment;
use Illuminate\Validation\Rule;

class CreateAttachmentRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('create', [Attachment::class, $this->get('project_id')]);
    }

    public function _rules(): array
    {
        // TODO: refactor this?
        $attachmentAbleType = $this->get('attachmentable_type');
        $attachmentAbleTypeExists = in_array($attachmentAbleType, Attachment::SUPPORTED_BY, true);

        $ruleForAttachmentAbleId = $attachmentAbleTypeExists ? Rule::exists($attachmentAbleType, 'id')
            : Rule::in(Attachment::SUPPORTED_BY); // TODO: make something better to make validation fail

        return [
            'attachment' => 'file|required|max:2000', // TODO: take max file size from php config
            'attachmentable_id' => ['required', 'integer', $ruleForAttachmentAbleId],
            'attachmentable_type' => ['required', Rule::in(Attachment::SUPPORTED_BY)],
        ];
    }
}
