<?php

namespace App\Helpers;

class StorageHelper
{
    /**
     * Whether the disk keeps files on the local filesystem (PVC / storage/app)
     * rather than on a remote object storage such as S3.
     *
     * Decided by the configured driver, so it stays correct under Storage::fake().
     */
    public static function isLocal(?string $disk = null): bool
    {
        $disk ??= config('filesystems.default');

        return config("filesystems.disks.$disk.driver") === 'local';
    }
}
