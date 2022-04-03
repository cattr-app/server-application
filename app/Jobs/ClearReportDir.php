<?php

namespace App\Jobs;

use App\Contracts\AppReport;
use App\Models\User;
use App\Notifications\ReportGenerated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Storage;
use Throwable;

class ClearReportDir implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    /**
     * @throws Throwable
     */
    public function __construct(private string $dir)
    {
    }

    public function handle(): void
    {
        Storage::drive('local')->deleteDirectory('reports/' . $this->dir);
    }
}
