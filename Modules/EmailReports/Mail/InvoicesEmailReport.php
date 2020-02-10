<?php

namespace Modules\EmailReports\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Files\TemporaryFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class EmailReportMail
 * @package Modules\EmailReports\Mail
 */
class InvoicesEmailReport extends Mailable
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
     * @var File
     */
    public $attachedFile;

    /**
     * @var string
     */
    public $fileName;

    /**
     * InvoicesEmailReport constructor.
     * @param string $fromDate
     * @param string $toDate
     * @param File $attachedFile
     * @param string $fileName
     */
    public function __construct(string $fromDate, string $toDate, File $attachedFile, string $fileName)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->attachedFile = $attachedFile;
        $this->fileName = $fileName;
    }

    /**
     * @return InvoicesEmailReport
     */
    public function build()
    {
        return $this->view("emailreports::invoicesReport")
                    ->attach($this->attachedFile, ['as' => $this->fileName]);
    }
}
