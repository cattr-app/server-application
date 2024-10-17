<?php

namespace App\Observers;

use App\Contracts\AttachmentAble;
use App\Contracts\AttachmentService;
use App\Helpers\AttachmentHelper;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;

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
        if (isset($requestData['attachmentsToRemove']) && count($requestData['attachmentsToRemove']) > 0) {
            $this->service->deleteAttachments($requestData['attachmentsToRemove']);
        }
    }

    public function parentUpdated(AttachmentAble $parent, $requestData): void
    {
        if ($parent instanceof Task && isset($parent->getChanges()['project_id'])) {
            $this->service->handleProjectChange($parent);
        }
        if (isset($requestData['attachmentsRelation']) && count($requestData['attachmentsRelation']) > 0) {
            $this->service->attach($parent, $requestData['attachmentsRelation']);
        }
        if (isset($requestData['attachmentsToRemove']) && count($requestData['attachmentsToRemove']) > 0) {
            $this->service->deleteAttachments($requestData['attachmentsToRemove']);
        }
    }

    public function parentDeleted(AttachmentAble $parent): void
    {
        $this->service->handleParentDeletion($parent);
    }

    public function projectDeleted(Project $project): void
    {
        $this->service->handleProjectDeletion($project);
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
            'event.after.action.attachment.create' => [[__CLASS__, 'attachmentCreated']],
            'event.after.action.projects.destroy' => [[__CLASS__, 'projectDeleted']]
        ], AttachmentHelper::getEvents(
            parentCreated: [[__CLASS__, 'parentCreated']],
            parentUpdated: [[__CLASS__, 'parentUpdated']],
            parentDeleted: [[__CLASS__, 'parentDeleted']],
        ));
    }

}
