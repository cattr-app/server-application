<?php

namespace App\Contracts;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;

interface AttachmentService
{
    /** Longest lifetime of a temporary download link: 7 days, the S3 (SigV4) presigning limit */
    public const MAX_TEMPORARY_URL_SECONDS = 7 * 24 * 60 * 60;

    public function storeFile(UploadedFile $file, Attachment $attachment): string|false;

    public function handleProjectChange(Task $parent): void;

    public function moveAttachment(Attachment $attachment, $newProjectId): void;

    public function deleteAttachment(Attachment $attachment): void;

    public function deleteAttachments(array $attachmentsIds): void;

    public function handleParentDeletion(AttachmentAble $parent): void;

    public function handleProjectDeletion(Project $project): void;

    public function fileExists(Attachment $attachment): bool;

    public function attach(AttachmentAble $parent, array $idsToAttach);

    public function getProjectPath(Project $project): string;

    public function getPath(Attachment $attachment): string;

    public function getFullPath(Attachment $attachment): string;

    /**
     * Presigned download URL when attachments are on object storage (S3); null for the local disk
     * (the signed attachment.temporary-download route streams the file then).
     * The expiration must not exceed MAX_TEMPORARY_URL_SECONDS.
     */
    public function getTemporaryUrl(Attachment $attachment, DateTimeInterface $expiresAt): ?string;

    public function getHashAlgo(): string;

    public function getHashSum(Attachment|string $attachment): false|string;

    public function getMimeType(Attachment|string $attachment): false|string;

    public function verifyHash(Attachment $attachment);
}
