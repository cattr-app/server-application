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
     * Lifetime of the unique lock: the dispatcher takes it and the worker releases it, so a lock
     * that is never released (crashed worker, cache store not shared with the worker) must expire
     * before the next daily cattr:attachments:verify run.
     *
     * @var int
     */
    public int $uniqueFor = 3600;

    /**
     * Seconds the worker lets the job run. On S3 verification downloads and hashes the whole
     * object (worst case a 512 MB attachment at ~5 Mbit/s), far beyond the 60 s worker default.
     * Must stay below the queue `retry_after` (QUEUE_RETRY_AFTER) and below $uniqueFor. The default
     * connection is `database`; with QUEUE_CONNECTION=sqs, which has no `retry_after`, the SQS queue
     * visibility timeout must be set above 900 s instead.
     *
     * @var int
     */
    public int $timeout = 900;

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
