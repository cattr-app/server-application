<?php

namespace App\Jobs;

use App\Contracts\AttachmentService;
use App\Models\Attachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class VerifyAttachmentHash implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public bool $deleteWhenMissingModels = true;

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
    public function handle(AttachmentService $service): void
    {
        $service->verifyHash($this->attachment);
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return $this->attachment->id;
    }

    public function failed(\Throwable $exception = null): void
    {
        Log::error('Job VerifyAttachmentHash Failed', [
            'attachment_id' => $this->attachment->id,
            'exception' => $exception
        ]);
    }
}
