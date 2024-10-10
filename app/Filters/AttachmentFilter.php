<?php

namespace App\Filters;

use App\Enums\AttachmentStatus;
use App\Helpers\AttachmentHelper;
use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class AttachmentFilter
{
    public function __construct()
    {
    }

    /**
     * Adds request rules to attachment parent
     * @param array $rules
     * @return array
     */
    public static function addRequestRulesForParent(array $rules): array
    {
        $rules['attachmentsRelation'] = 'sometimes|required|array';
        $rules['attachmentsRelation.*'] = [
            'sometimes',
            'required',
            'uuid',
            Rule::exists(Attachment::class, 'id')
        ];
        $rules['attachmentsToRemove'] = 'sometimes|required|array';
        $rules['attachmentsToRemove.*'] = [
            'sometimes',
            'required',
            'uuid',
            Rule::exists(Attachment::class, 'id')
        ];

        return $rules;
    }

    public static function prepareAttachmentCreateRequest($request) {
        /**
         * @var $file UploadedFile
         */
        $file = $request['attachment'];

        $request['user_id'] = auth()->user()->id;
        $request['status'] = AttachmentStatus::NOT_ATTACHED;
        $request['original_name'] = AttachmentHelper::getFileName($file);
        $request['mime_type'] = $file->getMimeType();
        $request['extension'] = $file->clientExtension();
        $request['size'] = $file->getSize();

        return $request;
    }

    public function subscribe(): array
    {
        return array_merge([
            'filter.request.attachment.create' => [[__CLASS__, 'prepareAttachmentCreateRequest']]
        ], AttachmentHelper::getFilters(
            addRequestRulesForParent: [[__CLASS__, 'addRequestRulesForParent']]
        ));
    }

}
