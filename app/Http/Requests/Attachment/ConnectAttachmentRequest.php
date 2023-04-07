<?php

namespace App\Http\Requests\Attachment;

use App\Helpers\AttachmentHelper;
use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use Illuminate\Validation\Rule;

// TODO: probable useless
class ConnectAttachmentRequest extends CattrFormRequest
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
        $able_type = $this->get('attachmentable_type');

        $ruleForAbleId = AttachmentHelper::isAble($able_type) ? Rule::exists($able_type, 'id')
            : Rule::in(['DUMMY_VALUE']); // TODO: write something better to make validation fail

        return [
            'attachmentable_id' => ['required', 'integer', $ruleForAbleId],
            'attachmentable_type' => ['required', Rule::in(AttachmentHelper::getAbles())],
        ];
    }
}
