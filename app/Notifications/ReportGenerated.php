<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ReportGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Attachment buffer: up to 1 MiB in memory, the rest spills to a temporary file.
     */
    private const BUFFER = 'php://temp/maxmemory:1048576';

    public function __construct(private $fileName, private $drive)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('In attachment see report you have requested.')
                    ->attachData(
                        $this->bufferReport(),
                        basename($this->fileName),
                        ['mime' => Storage::drive($this->drive)->mimeType($this->fileName) ?: null],
                    );
    }

    /**
     * Copy the report into a seekable temporary stream instead of loading it into a string
     * (Attachment::fromStorageDisk() calls Storage::get()). Symfony Mime accepts a resource as the
     * attachment body and encodes it chunk by chunk, so the report is never held in memory as a whole.
     * The copy also makes the body seekable (an S3 read stream is not), so the message can be rendered again.
     *
     * @return resource
     */
    private function bufferReport()
    {
        $source = Storage::drive($this->drive)->readStream($this->fileName);
        if (!is_resource($source)) {
            throw new RuntimeException("Unable to read report $this->fileName from the $this->drive disk");
        }

        try {
            $buffer = fopen(self::BUFFER, 'w+b');
            stream_copy_to_stream($source, $buffer);
            rewind($buffer);
        } finally {
            fclose($source);
        }

        return $buffer;
    }

    public function toArray($notifiable): array
    {
        return [
            'path' => $this->fileName,
        ];
    }
}
