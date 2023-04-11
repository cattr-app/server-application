<?php

namespace App\Filters;

use App\Enums\AttachmentStatus;
use App\Helpers\AttachmentHelper;
use App\Models\Attachment;
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
            Rule::exists(Attachment::TABLE, 'id')->where('status', AttachmentStatus::NOT_ATTACHED->value)
        ];

        return $rules;
    }

    public function subscribe(): array
    {
        return AttachmentHelper::getFilters(
            [[__CLASS__, 'addRequestRulesForParent']]
        );
    }

}
