<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Storage;

class ReportGenerated extends Notification implements ShouldQueue
{
    use Queueable;

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
                    ->attach(Storage::drive($this->drive)->path($this->fileName));
    }

    public function toArray($notifiable): array
    {
        return [
            'path' => $this->fileName,
        ];
    }
}
