<?php

namespace Modules\EmailReports\Entities;

use Modules\EmailReports\Models\EmailReports;

/**
 * Class SavedReportsRepository
 * @package Modules\EmailReports\Entities
 */
class ReportsSender
{
    public function send()
    {
        $map = [
            EmailReports::DAILY,
            EmailReports::WEEKLY,
            EmailReports::MONTHLY
        ];

        foreach ($map as $frequency) {
            $emailReports = EmailReports::whereFrequency($frequency)->get();

            foreach ($emailReports as $emailReport) {
                if (!EmailReports::checkIsReportSendDay($emailReport->sending_day, $frequency)) {
                    continue;
                }

                $exporter = app(EmailReports::getExporterClass($emailReport));
                $exporter->sendEmail($emailReport, $frequency);
            }
        }
    }
}
