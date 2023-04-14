<?php

namespace App\Services;

use App\Contracts\AttachmentAble;
use App\Enums\AttachmentStatus;
use App\Jobs\VerifyAttachmentHash;
use App\Models\Attachment;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Storage;

class AttachmentService implements \App\Contracts\AttachmentService
{
    public const DISK = 'attachments';
    private readonly FilesystemAdapter $storage;

    public function __construct()
    {
        $this->storage = Storage::disk('attachments');
    }
    public function storeFile(UploadedFile $file, Attachment $attachment): string|false
    {
        return $file->storeAs(
            $this->getPath($attachment), ['disk' => $this::DISK]
        );
    }

    public function attach(AttachmentAble $parent, array $idsToAttach): void
    {
        $parentId = $parent->id;
        $parentType = $parent::TYPE;
        $projectId = $parent->getProjectId();
        Attachment::whereIn('id', $idsToAttach)
            ->each(function (Attachment $attachment) use ($parentType, $parentId, $projectId) {
                $tmpPath = $this->getPath($attachment);

                if ($this->storage->exists($tmpPath)){
                    $attachment->attachmentable_id = $parentId;
                    $attachment->attachmentable_type = $parentType;
                    $attachment->status = AttachmentStatus::PROCESSING;
                    $attachment->project_id = $projectId;
                    $attachment->save();

                    $newPath = $this->getPath($attachment);
                    $this->storage->move($tmpPath, $newPath);

                    VerifyAttachmentHash::dispatch($attachment)->afterCommit();
                }
            });
    }

    public function getPath(Attachment $attachment): string
    {
        return match($attachment->status) {
            AttachmentStatus::NOT_ATTACHED => "users/{$attachment->user_id}/{$attachment->id}",
            AttachmentStatus::PROCESSING,
            AttachmentStatus::GOOD,
            AttachmentStatus::BAD => "projects/{$attachment->project_id}/{$attachment->id}",
        };
    }

    public function getHashAlgo(): string
    {
        return $this->storage->getConfig()['checksum_algo'] ?? 'sha512/256';
    }

    public function getHashSum(Attachment $attachment): false|string
    {
        return $this->storage->checksum($this->getPath($attachment), ['checksum_algo' => $this->getHashAlgo()]);
    }

    public function verifyHash(Attachment $attachment): void
    {
        if ($attachment->status === AttachmentStatus::PROCESSING && $hash = $this->getHashSum($attachment)) {
            $attachment->hash = $hash;
            $attachment->status = AttachmentStatus::GOOD;
        } elseif (
            $attachment->status === AttachmentStatus::GOOD && $attachment->hash !== $this->getHashSum($attachment)
        ) {
            $attachment->status = AttachmentStatus::BAD;
        }

        $attachment->save();
    }
}
