<?php

namespace Modules\EmailReports\Entities;

use Modules\EmailReports\Models\EmailReports;
use Modules\EmailReports\StatisticExports\Invoices;
use Modules\EmailReports\StatisticExports\ProjectReport;
use Modules\Reports\Exports\ProjectExport;

/**
 * Class SavedReportsRepository
 * @package Modules\EmailReports\Entities
 */
class ReportsSender extends ProjectExport
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
