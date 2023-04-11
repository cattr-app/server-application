<?php

namespace App\Observers;

use App\Contracts\AttachmentAble;
use App\Enums\AttachmentStatus;
use App\Helpers\AttachmentHelper;
use App\Jobs\ValidateAttachmentHash;
use App\Models\Attachment;
use Storage;

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

    public function parentUpdated(AttachmentAble $parent, $requestData): void
    {
        if (isset($requestData['attachmentsRelation']) && count($requestData['attachmentsRelation']) > 0) {
            $parentId = $parent->id;
            $parentType = $parent::TABLE;
            $projectId = $parent->getProjectId();
            Attachment::whereIn('id', $requestData['attachmentsRelation'])
                ->each(static function (Attachment $attachment) use ($parentType, $parentId, $projectId) {
                    dump(['exists' => Storage::exists("user/{$attachment->user_id}/{$attachment->id}.png")]);
                    if (Storage::exists("user/{$attachment->user_id}/{$attachment->id}.{$attachment->extension}")){
                        $attachment->attachmentable_id = $parentId;
                        $attachment->attachmentable_type = $parentType;
                        $attachment->status = AttachmentStatus::PROCESSING;
                        $attachment->project_id = $projectId;
                        $attachment->save();

                        Storage::move("user/{$attachment->user_id}/{$attachment->id}.{$attachment->extension}", "project/{$projectId}/{$attachment->id}.{$attachment->extension}");
//                      TODO: [ ] calculate files hash by utilizing some AttachmentService
                        ValidateAttachmentHash::dispatch($attachment)->afterCommit();
                    }
                });
        }
//        dump([
//            'updated' => $parent,
//            '$requestData' => $requestData
//        ]);
    }

    public function parentDeleted(AttachmentAble $parent) {
//      TODO: [ ] should we have SoftDelete?
        dump([
            'deleted' => $parent
        ]);
    }

    public function subscribe(): array
    {
        return AttachmentHelper::getEvents(
            [[__CLASS__, 'parentCreated']],
            [[__CLASS__, 'parentUpdated']],
            [[__CLASS__, 'parentDeleted']],
        );
    }

}
