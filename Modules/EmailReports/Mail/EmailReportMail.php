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
        $file = fopen('php://memory', 'w+');
        throw_if(!$file, new \Exception('Temp csv file wasnt created!'));

        $file = $this->buildReportData($file, $this->reportData);

        return $this->view("report")
                    ->attachData(stream_get_contents($file), $filename)
                    ->closeFile($file);
    }

    private function buildReportData($file, $reports)
    {
        $headers = ['Project', 'Programmer', 'Task', 'Hours', 'Corrected Decimal Hours', 'Rate ($)', 'Summary ($)'];
        fputcsv($file, $headers);
        fputcsv($file, []); // We will place 'Total' row here

        $totalInvoiceHours = null;
        $totalInvoiceSummary = null;

        foreach ($reports as $project) {
            $timeForProjectDecimal = 0;
            foreach ($project['users'] as $user) {
                $totalSummaryForProject = 0;
                foreach ($user['tasks'] as $task) {
                    $time = date('H:i:s', $task['duration']);
                    $timeDecimal = number_format($this->timeToDecimal($time), 3);
                    $timeForProjectDecimal += $timeDecimal;
                    $totalInvoiceHours += $timeDecimal;

                    $summary = $user['rate'] * $task['duration'] / 60 / 60;
                    $summary = number_format($summary, 2);
                    $totalSummaryForProject += $summary;

                    $userRate = number_format($user['rate'], 2) ? '$' . number_format($user['rate'],2) : '';

                    fputcsv($file, [
                        $project['name'], $user['full_name'], $task['task_name'], $time, $timeDecimal, $userRate, $summary ? '$' . $summary : ''
                    ]);
                }
            }
            $timeTotalForProject = $this->convertTimeFromDecimal($timeForProjectDecimal);
            $timeTotalDecimalForProject = number_format($this->timeToDecimal($timeTotalForProject), 3);
            $totalInvoiceSummary += $totalSummaryForProject ?? 0;
            fputcsv($file, [
                'Total for the project ' . $project['name'], '', '', $timeTotalForProject, $timeTotalDecimalForProject, '', $totalSummaryForProject ? '$' . $totalSummaryForProject : ''
            ]);
        }
        $totalInvoiceHours = $this->convertTimeFromDecimal($totalInvoiceHours);
        $totalInvoiceHoursDecimal = $this->timeToDecimal($totalInvoiceHours);
        fputcsv($file, [
            'Total', '', '', $totalInvoiceHours, $totalInvoiceHoursDecimal, '', $totalInvoiceSummary ? '$' . $totalInvoiceSummary : ''
        ]);

        rewind($file);
        $file = $this->replaceRows($file);

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

    private function convertTimeFromDecimal($decimalTime) {
        return floor($decimalTime) . ':' . (floor($decimalTime * 60) % 60) . ':' . floor($decimalTime * 3600) % 60;
    }

    private function replaceRows($file)
    {
        $csvData = stream_get_contents($file, fstat($file)['size']);
        $csvRows = explode("\n", $csvData);
        $csvRows[1] = $csvRows[count($csvRows) - 2]; // Last row in always '\n' so pick previous
        unset($csvRows[count($csvRows) - 2]); // Unset 'Total' row in the end of file

        fclose($file);

        $replacedRowsFile = fopen('php://memory', 'w+');
        foreach ($csvRows as $csvRow) {
            fputcsv($replacedRowsFile, explode(',', str_replace('"', '', $csvRow)));
        }

        rewind($replacedRowsFile);
        return $replacedRowsFile;
    }
}
