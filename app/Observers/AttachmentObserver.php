<?php

namespace App\Observers;

use App\Contracts\AttachmentAble;
use App\Contracts\AttachmentService;
use App\Helpers\AttachmentHelper;
use App\Models\Attachment;

class AttachmentObserver
{
    public function __construct(private readonly AttachmentService $service)
    {
    }

    public function parentCreated(AttachmentAble $parent, $requestData): void
    {
        if (isset($requestData['attachmentsRelation']) && count($requestData['attachmentsRelation']) > 0) {
            $this->service->attach($parent, $requestData['attachmentsRelation']);
        }
    }

    public function parentUpdated(AttachmentAble $parent, $requestData): void
    {
//      TODO: [ ] if task moves to another project we should create new task and copy main files
        if (isset($requestData['attachmentsRelation']) && count($requestData['attachmentsRelation']) > 0) {
            $this->service->attach($parent, $requestData['attachmentsRelation']);
        }
    }

    public function parentDeleted(AttachmentAble $parent): void
    {
//      TODO: [ ] delete
        dump([
            'deleted' => $parent
        ]);
    }

    public function attachmentCreated(Attachment $attachment, array $requestData): void
    {
        if ($this->service->storeFile($requestData['attachment'], $attachment) === false){
//          TODO: throw exception or abort_if?
        }
    }

    public function subscribe(): array
    {
        return array_merge([
            'event.after.action.attachment.create' => [[__CLASS__, 'attachmentCreated']]
        ], AttachmentHelper::getEvents(
            parentCreated: [[__CLASS__, 'parentCreated']],
            parentUpdated: [[__CLASS__, 'parentUpdated']],
            parentDeleted: [[__CLASS__, 'parentDeleted']],
        ));
    }

}
