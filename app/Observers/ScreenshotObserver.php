<?php

namespace App\Observers;

use App\Models\Screenshot;
use Storage;

class ScreenshotObserver
{
    /**
     * Handle the screenshot "deleted" event.
     *
     * @param Screenshot $screenshot
     * @return void
     */
    public function deleted(Screenshot $screenshot): void
    {
        $this->executeDeletion($screenshot);
    }

    /**
     * Handle the screenshot "force deleted" event.
     *
     * @param Screenshot $screenshot
     * @return void
     */
    public function forceDeleted(Screenshot $screenshot): void
    {
        $this->executeDeletion($screenshot);
    }

    private function executeDeletion(Screenshot $screenshot): void
    {
        if (Screenshot::where(['path' => $screenshot->path])->count() !== 1) {
            return;
        }

        Storage::delete([$screenshot->path, $screenshot->thumbnail_path]);
    }
}
