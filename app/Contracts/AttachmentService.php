<?php

namespace App\Contracts;

use App\Models\Attachment;
use App\Models\Project;
use Illuminate\Http\UploadedFile;

interface AttachmentService
{
    public function storeFile(UploadedFile $file, Attachment $attachment): string|false;

    public function handleParentDeletion(AttachmentAble $parent): void;

    public function handleProjectDeletion(Project $project): void;

    public function fileExists(Attachment $attachment): bool;

    public function attach(AttachmentAble $parent, array $idsToAttach);

    public function getProjectPath(Project $project): string;

    public function getPath(Attachment $attachment): string;

    public function getHashAlgo(): string;

    public function getHashSum(Attachment|string $attachment): false|string;

    public function getMimeType(Attachment|string $attachment): false|string;

    public function verifyHash(Attachment $attachment);
}
