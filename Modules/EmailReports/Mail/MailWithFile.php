<?php

namespace Modules\EmailReports\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailWithFile extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $fromDate;
    public string $toDate;
    public string $filePath;
    public string $fileName;

    public function __construct(string $fromDate, string $toDate, string $filePath, string $fileName)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    public function build(): self
    {
        return $this->view("emailreports::mail")
            ->attach($this->filePath, ['as' => $this->fileName]);
    }
}
