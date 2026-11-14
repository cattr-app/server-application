<?php

namespace App\Enums;

enum OfflineScreenshotUploadStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function isFinished(): bool
    {
        return $this === self::COMPLETED || $this === self::FAILED;
    }
}
