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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;
use Storage;
use Throwable;

class GenerateAndSendReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 60;
    private string $dir;

    private const STORAGE_DRIVE = 'public';

    public function getPublicPath(): string
    {
        return Storage::drive(self::STORAGE_DRIVE)->url($this->getReportPath());
    }

    /**
     * @throws Throwable
     */
    public function __construct(
        private AppReport $report,
        private User $user,
        private ?string $type,
    ) {
        $this->dir = Str::uuid();

        throw_unless($type, ValidationException::withMessages(['Wrong accept mime type']));
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        Storage::drive(self::STORAGE_DRIVE)->makeDirectory("reports/$this->dir");

        $fileName = $this->getReportPath();

        $this->report->store($fileName, self::STORAGE_DRIVE, $this->type === 'pdf' ? Excel::MPDF : Str::ucfirst($this->type));

        $this->user->notify((new ReportGenerated($fileName, self::STORAGE_DRIVE)));

        $dir = $this->dir;

        dispatch(
            static fn() => Storage::drive(self::STORAGE_DRIVE)->deleteDirectory("reports/$dir")
        )->delay(now()->addHour());
    }

    public function uniqueId(): string
    {
        return "{$this->user->id}_{$this->report->getReportId()}_$this->type";
    }

    private function getReportPath(): string
    {
        return "/reports/$this->dir/{$this->report->getLocalizedReportName()}.$this->type";
    }
}
