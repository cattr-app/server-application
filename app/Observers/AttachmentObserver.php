<?php

namespace App\Observers;

use App\Contracts\AttachmentAble;
use App\Helpers\AttachmentHelper;

class AttachmentObserver
{
    public function __construct()
    {
    }

    public function parentCreated(AttachmentAble $parent) {
//      TODO: [ ] connect attachments
        dump([
            'created' => $parent
        ]);
    }

    public function parentUpdated(AttachmentAble $parent, $requestData) {
//      TODO: [ ] connect attachments
        dump([
            'updated' => $parent,
            '$requestData' => $requestData
        ]);
    }

    public function parentDeleted(AttachmentAble $parent) {
        dump([
            'deleted' => $parent
        ]);
    }

    public static function addRequestRulesForParent($rules) {
//      TODO: [ ] add rules for attachments
        $rules['attachmentsRelation'] = 'sometimes|required|array';
        $rules['attachmentsRelation.*'] = 'sometimes|required|string';
        dump([
            'rulesrulesrules' => $rules,
        ]);
        return $rules;
    }

    public function subscribe(): array
    {
        return AttachmentHelper::getEventsAndFilters(
            [[__CLASS__, 'parentCreated']],
            [[__CLASS__, 'parentUpdated']],
            [[__CLASS__, 'parentDeleted']],
            [[__CLASS__, 'addRequestRulesForParent']]
        );
    }

}
