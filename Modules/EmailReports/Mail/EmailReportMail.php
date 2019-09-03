<?php


namespace Modules\EmailReports\Mail;


use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fromDate;

    public $toDate;

    public $reportData;

    public function __construct(string $fromDate, string $toDate, array $reportData)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->reportData = $reportData;
    }

    public function build()
    {
        $filename = 'Amazingtime Invoice Report.csv';
        $file = fopen('php://temp', 'w+');
        throw_if(!$file, new \Exception('Temp csv file wasnt created!'));

        $file = $this->buildReportData($file, $this->reportData);
        rewind($file);

        return $this->view("report")
                    ->attachData(stream_get_contents($file), $filename)
                    ->closeFile($file);
    }

    private function buildReportData($file, $reports)
    {
        $headers = ['Project', 'Programmer', 'Task', 'Hours', 'Corrected Decimal Hours', 'Rate, $', 'Summary, $'];
        fputcsv($file, $headers);

        $totalInvoiceHours = null;
        $totalInvoiceSummary = null;

        foreach ($reports as $project) {
            foreach ($project['users'] as $user) {
                $totalSummaryForProject = 0;
                foreach ($user['tasks'] as $task) {
                    $time = date('H:i:s', $task['duration']);
                    $timeDecimal = $this->timeToDecimal($time);
                    $summary = $user['rate'] * $task['duration'] / 60 / 60;
                    $summary = number_format($summary, 2);
                    $totalSummaryForProject += $summary;
                    fputcsv($file, [
                        $project['name'], $user['full_name'], $task['task_name'], $time, $timeDecimal, number_format($user['rate'], 2), $summary
                    ]);
                }
            }
            $timeTotalForProject = date('H:i:s', $project['project_time']);
            $timeTotalDecimalForProject = $this->timeToDecimal($timeTotalForProject);
            $totalInvoiceHours += $project['project_time'];
            $totalInvoiceSummary += $totalSummaryForProject ?? 0;
            fputcsv($file, [
                'Total for the project ' . $project['name'], '', '', '', $timeTotalForProject, $timeTotalDecimalForProject, '', $totalSummaryForProject !== 0 ? $totalSummaryForProject : ''
            ]);
        }
        $totalInvoiceHours = date('H:i:s', $totalInvoiceHours);
        $totalInvoiceHoursDecimal = $this->timeToDecimal($totalInvoiceHours);
        fputcsv($file, [
            'Total', '', '', '', $totalInvoiceHours, $totalInvoiceHoursDecimal, '', $totalInvoiceSummary !== 0 ? $totalInvoiceSummary : ''
        ]);

        return $file;
    }

    private function timeToDecimal(string $time)
    {
        $hms = explode(':', $time);
        return ($hms[0] + ($hms[1] / 60) + ($hms[2] / 3600));
    }

    private function closeFile($file)
    {
        fclose($file);
        return $this;
    }
}
