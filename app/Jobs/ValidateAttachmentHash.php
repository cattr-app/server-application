<?php

namespace App\Jobs;

use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Storage;

class ValidateAttachmentHash implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Attachment $attachment)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        dump(['ValidateAttachmentHash' => $this->attachment]);
        if ($this->attachment->status === AttachmentStatus::PROCESSING
            && Storage::exists("project/{$this->attachment->project_id}/{$this->attachment->id}.{$this->attachment->extension}")
            && $hash = hash_file('sha512/256',
                Storage::path("project/{$this->attachment->project_id}/{$this->attachment->id}.{$this->attachment->extension}"))
        ) { // Initial hash calculation
            $this->attachment->hash = $hash;
            $this->attachment->status = AttachmentStatus::GOOD;
        } elseif (
            $this->attachment->status === AttachmentStatus::GOOD
            && Storage::exists("project/{$this->attachment->project_id}/{$this->attachment->id}.{$this->attachment->extension}")
            && ($hash = hash_file('sha512/256',
                    Storage::path("project/{$this->attachment->project_id}/{$this->attachment->id}.{$this->attachment->extension}")))
            && $this->attachment->hash === $hash
        ) {
//            Hash is good :)
        } else {
            $this->attachment->status = AttachmentStatus::BAD;
        }
        $this->attachment->save();
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->attachment->id;
    }
}
