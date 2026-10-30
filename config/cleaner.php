<?php

use App\Contracts\ScreenshotService;

return [
    'total_space' => env(
        'SCREENSHOTS_STORAGE_SIZE_BYTES',
        disk_total_space(ScreenshotService::getFullPath())
    ),
    'threshold' => env('CLEANER_THRESHOLD_USED_SPACE_PERCENTAGE', 75),
    'waterline' => env('CLEANER_WATERLINE_PERCENTAGE', 15),
    'page_size' => 10,
    // Seconds the queue worker lets a requested cleanup run, must stay below QUEUE_RETRY_AFTER
    'job_timeout' => (int)env('CLEANER_JOB_TIMEOUT', 900),
    // A requested cleanup no worker has picked up within this time is reported as failed
    'queued_timeout' => (int)env('CLEANER_QUEUED_TIMEOUT', 3600),
    // A running cleanup without a heartbeat (one per removed page of screenshots) within this time is failed.
    // Kept above job_timeout: a run the worker has not killed yet must not be taken over by a new one.
    'running_timeout' => max(
        (int)env('CLEANER_RUNNING_TIMEOUT', 960),
        (int)env('CLEANER_JOB_TIMEOUT', 900) + 60
    ),
];
