<?php

namespace Modules\EmailReports\Entities;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\EmailReports\Mail\MailWithFile;
use Modules\EmailReports\Models\EmailReports;

class ReportsSender
{
    public const TEMP_FILE_DIR = 'EmailReports';

    public function send(): void
    {
        $map = [
            EmailReports::DAILY,
            EmailReports::WEEKLY,
            EmailReports::MONTHLY
        ];

        foreach ($map as $frequency) {
            $emailReports = EmailReports::whereFrequency($frequency)->get();
            if (empty($emailReports->toArray())) {
                continue;
            }

            foreach ($emailReports as $emailReport) {
                if (!EmailReports::checkIsReportSendDay($emailReport->sending_day, $frequency)) {
                    continue;
                }
                $this->sendEmail($emailReport, $frequency);
            }
        }
    }

    /**
     * @param $emailReport EmailReports
     * @param $frequency
     *
     * @throws Exception
     */
    public function sendEmail(EmailReports $emailReport, $frequency): void
    {
        $dates = EmailReports::getDatesToWorkWith($frequency);

        $queryData = [
            'start_at' => $dates['startAt'],
            'end_at' => $dates['endAt'],
            'uids' => User::withTrashed()->get()->pluck('id')->toArray(),
            'pids' => $emailReport->projects()->pluck('project_id')->toArray(),
        ];

        $exporter = EmailReports::getExporterClass($emailReport);
        $typeExporter = EmailReports::getTypeExporter($emailReport, $exporter);

        $filePath = $this->createReportPath($emailReport->name . '.' . $typeExporter->getFileNameType());
        $isStored = $typeExporter->store($exporter->exportCollection($queryData), $filePath);

        if (!$isStored) {
            Log::error('Temp File was not saved!');
            return;
        }

        $recipients = $emailReport->emails()->pluck('email')->toArray();
        Mail::to($recipients)
            ->send(
                new MailWithFile(
                    date('l\, m Y', strtotime($dates['startAt'])),
                    date('l\, m Y', strtotime($dates['endAt'] ?? 'yesterday')),
                    Storage::path($filePath),
                    $emailReport->name . '.' . $typeExporter->getFileNameType()
                )
            );

        if (Mail::failures()) {
            Log::error('Unfortunately we wasn\'t able to send messages for this recipients: ' . var_dump(Mail::failures()));
        }

        if (Storage::exists($filePath) && !Storage::delete($filePath)) {
            Log::error('File ' . $filePath . ' was not deleted!');
            return;
        }
    }

    /**
     * @param $fileName
     * @return string
     */
    private function createReportPath($fileName): string
    {
        return self::TEMP_FILE_DIR . '/' . $fileName;
    }
}
