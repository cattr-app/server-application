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
use App\Jobs\VerifyAttachmentHash;

#[AsCommand(name: 'cattr:attachments:verify')]
class VerifyAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:attachments:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify hash of all files';

    /**
     * Execute the console command.
     */
    public function handle(AttachmentService $service): void
    {
        $this->withProgressBar(Attachment::lazyById(), fn($attachment) => VerifyAttachmentHash::dispatch($attachment));
    }
}
