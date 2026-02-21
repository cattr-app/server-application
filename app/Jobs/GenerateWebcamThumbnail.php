<?php

namespace App\Jobs;

use App\Contracts\WebcamScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWebcamThumbnail implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function __construct(private readonly TimeInterval|int $interval)
    {
    }

    public function handle(WebcamScreenshotService $webcamScreenshotService): void
    {
        $webcamScreenshotService->createWebcamThumbnail($this->interval);
    }

    public function uniqueId(): string
    {
        return (string)(optional($this->interval)->id ?: $this->interval);
    }
}
