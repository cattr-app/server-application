<?php

namespace Modules\EmailReports\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Files\TemporaryFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class EmailReportMail
 * @package Modules\EmailReports\MailWithFile
 */
class MailWithFile extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var string
     */
    public $fromDate;

    /**
     * @var string
     */
    public $toDate;

    /**
     * @var string
     */
    public $filePath;

    /**
     * @var string
     */
    public $fileName;

    /**
     * MailWithFile constructor.
     * @param string $fromDate
     * @param string $toDate
     * @param string $filePath
     * @param string $fileName
     */
    public function __construct(string $fromDate, string $toDate, string $filePath, string $fileName)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->filePath = $filePath;
        $this->fileName = $fileName;
    }

    /**
     * @return MailWithFile
     */
    public function build()
    {
        return $this->view("emailreports::mail")
                    ->attach($this->filePath, ['as' => $this->fileName]);
    }
}
