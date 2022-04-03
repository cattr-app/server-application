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
use Storage;
use Throwable;

class GenerateAndSendReport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    /**
     * @throws Throwable
     */
    public function __construct(private AppReport $report, private User $user, private ?string $type)
    {
        throw_unless($type, ValidationException::withMessages(['Wrong accept mime type']));
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $dir = Str::uuid();

        Storage::makeDirectory("reports/$dir");

        $fileName = "/reports/$dir/" . $this->report->getLocalizedReportName() . '.' . ($this->type === 'mpdf' ? 'pdf' : $this->type);

        $this->report->store($fileName, 'local', ucfirst($this->type));

        $this->user->notify((new ReportGenerated($fileName)));

        ClearReportDir::dispatch($dir)->delay(now()->addHour());
    }

    public function uniqueId(): string
    {
        return $this->user->id . $this->report->getReportId() . $this->type;
    }
}
