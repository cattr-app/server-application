<?php

namespace App\Console\Commands;

use App\Contracts\AttachmentService;
use App\Models\Attachment;
use App\Models\SusFiles;
use Arr;
use Illuminate\Console\Command;
use Storage;
use Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:attachments:find-sus-files')]
class FindSusFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:attachments:find-sus-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find files that exist inside attachments projects folder but not in DB';

    /**
     * Execute the console command.
     */
    public function handle(AttachmentService $service): void
    {
        SusFiles::query()->delete();

        $this->withProgressBar(
            Storage::disk('attachments')->allFiles('projects'),
            function ($filePath) use ($service) {
                $fileName = Str::of($filePath)->basename()->toString();

                if (Attachment::whereId($fileName)->exists() === false) {
                    SusFiles::create([
                        'path' => $filePath,
                        'mime_type' => $service->getMimeType($filePath),
                        'hash' => $service->getHashSum($filePath),
                    ]);
                }
            }
        );
    }
}
