<?php

namespace App\Services;

use App\Contracts\AttachmentAble;
use App\Enums\AttachmentStatus;
use App\Jobs\VerifyAttachmentHash;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Storage;

class AttachmentService implements \App\Contracts\AttachmentService
{
    public const DISK = 'attachments';
    private readonly FilesystemAdapter $storage;

    public function __construct()
    {
        $this->storage = Storage::disk($this::DISK);
    }
    public function storeFile(UploadedFile $file, Attachment $attachment): string|false
    {
        return $file->storeAs(
            $this->getPath($attachment),
            ['disk' => $this::DISK]
        );
    }

    public function handleProjectChange(Task $parent): void
    {
        $newProjectId = $parent->getProjectId();
        $parent->attachmentsRelation()->lazyById()
            ->each(fn(Attachment $attachment) => $this->moveAttachment($attachment, $newProjectId));

        $parent->comments()->lazyById()
            ->each(fn(TaskComment $comment) => $comment->attachmentsRelation()->lazyById()
                ->each(fn(Attachment $attachment) => $this->moveAttachment($attachment, $newProjectId)));
    }

    public function moveAttachment(Attachment $attachment, $newProjectId): void
    {
        if ($attachment->status === AttachmentStatus::NOT_ATTACHED) {
            return;
        }
        $oldPath = $this->getPath($attachment);
        $oldProjectId = $attachment->project_id;
        $attachment->project_id = $newProjectId;
        $attachment->saveQuietly();
        $newPath = $this->getPath($attachment);

        $fileExists = $this->storage->exists($oldPath);
        if (!$fileExists || !$this->storage->move($oldPath, $newPath)) {
            $attachment->project_id = $oldProjectId;
            $attachment->saveQuietly();
            \Log::error("Unable to move attachment`s file", [
                'attachment_id' => $attachment->id,
                'attachmentable_id' => $attachment->attachmentable_id,
                'attachmentable_type' => $attachment->attachmentable_type,
                'attachment_project_id' => $attachment->project_id,
                'old_attachment_project_id' => $oldProjectId,
                'old_path' => $oldPath,
                'new_path' => $newPath
            ]);
        }
    }

    public function deleteAttachment(Attachment $attachment): void
    {
        if (($fileExists = $this->fileExists($attachment)) && $this->deleteFile($attachment)) {
            $attachment->deleteQuietly();
        } elseif (!$fileExists) {
            $attachment->deleteQuietly();
        } else {
            \Log::error("Unable to delete attachment`s file", [
                'attachment_id' => $attachment->id,
                'attachmentable_id' => $attachment->attachmentable_id,
                'attachmentable_type' => $attachment->attachmentable_type,
                'attachment_project_id' => $attachment->project_id,
                'path' => $this->getPath($attachment)
            ]);
        }
    }

    public function deleteAttachments(array $attachmentsIds): void
    {
        Attachment::whereIn('id', $attachmentsIds)
            ->each(fn (Attachment $attachment) => $this->deleteAttachment($attachment));
    }

    public function handleParentDeletion(AttachmentAble $parent): void
    {
        $parent->attachmentsRelation()->lazyById()
            ->each(fn (Attachment $attachment) => $this->deleteAttachment($attachment));
    }

    public function handleProjectDeletion(Project $project): void
    {
        Attachment::whereProjectId($project->id)->delete();

        if ($this->storage->directoryExists($this->getProjectPath($project)) && !$this->storage->deleteDirectory($this->getProjectPath($project))) {
            \Log::error("Unable to delete project's attachments directory", [
                'project_id' => $project->id,
                'path' => $this->getProjectPath($project)
            ]);
        }
    }

    public function fileExists(Attachment $attachment): bool
    {
        return $this->storage->exists($this->getPath($attachment));
    }

    private function deleteFile(Attachment $attachment): bool
    {
        return $this->storage->delete($this->getPath($attachment));
    }

    public function attach(AttachmentAble $parent, array $idsToAttach): void
    {
        $projectId = $parent->getProjectId();
        Attachment::whereIn('id', $idsToAttach)
            ->each(function (Attachment $attachment) use ($parent, $projectId) {
                if ($attachment->status !== AttachmentStatus::NOT_ATTACHED) {
                    return;
                }
                $tmpPath = $this->getPath($attachment);

                if ($this->storage->exists($tmpPath)) {
                    $attachment->attachmentAbleRelation()->associate($parent);
                    $attachment->status = AttachmentStatus::PROCESSING;
                    $attachment->project_id = $projectId;
                    $attachment->saveQuietly();

                    $newPath = $this->getPath($attachment);
                    $this->storage->move($tmpPath, $newPath);

                    VerifyAttachmentHash::dispatch($attachment)->afterCommit();
                }
            });
    }

    public function getProjectPath(Project $project): string
    {
        return "projects/{$project->id}";
    }

    public function getPath(Attachment $attachment): string
    {
        return match ($attachment->status) {
            AttachmentStatus::NOT_ATTACHED => "users/{$attachment->user_id}/{$attachment->id}",
            AttachmentStatus::PROCESSING,
            AttachmentStatus::GOOD,
            AttachmentStatus::BAD => "projects/{$attachment->project_id}/{$attachment->id}",
        };
    }

    public function getFullPath(Attachment $attachment): string
    {
        return $this->storage->path($this->getPath($attachment));
    }

    public function readStream(Attachment $attachment)
    {
        return $this->storage->readStream($this->getPath($attachment));
    }

    public function getHashAlgo(): string
    {
        return $this->storage->getConfig()['checksum_algo'] ?? 'sha512/256';
    }

    public function getHashSum(Attachment|string $attachment): false|string
    {
        return $this->storage->checksum($this->callIfInstance($attachment, 'getPath'), ['checksum_algo' => $this->getHashAlgo()]);
    }

    public function getMimeType(Attachment|string $attachment): false|string
    {
        return $this->storage->mimeType($this->callIfInstance($attachment, 'getPath'));
    }

    private function callIfInstance(Attachment|string $attachment, $action)
    {
        return $attachment instanceof Attachment ? $this->{$action}($attachment) : $attachment;
    }

    public function verifyHash(Attachment $attachment): void
    {
        if ($attachment->status === AttachmentStatus::PROCESSING && $hash = $this->getHashSum($attachment)) {
            $attachment->hash = $hash;
            $attachment->status = AttachmentStatus::GOOD;
        } elseif ($attachment->status === AttachmentStatus::GOOD && $attachment->hash !== $this->getHashSum($attachment)
        ) {
            $attachment->status = AttachmentStatus::BAD;
        }

        $attachment->save();
    }
}
