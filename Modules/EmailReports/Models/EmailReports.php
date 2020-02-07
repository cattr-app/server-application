<?php

namespace Modules\EmailReports\Models;

use App\Models\AbstractModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Maatwebsite\Excel\Excel;
use Modules\EmailReports\StatisticExports\Invoices;
use Modules\EmailReports\StatisticExports\ProjectReport;

/**
 * Class EmailReports
 * @package Modules\EmailReports\Models
 */
class EmailReports extends AbstractModel
{
    const SPACE = " ";

    const DAILY = 0;
    const WEEKLY = 1;
    const MONTHLY = 2;

    const FREQUENCY = [
        self::DAILY => 'Daily',
        self::WEEKLY => 'Weekly',
        self::MONTHLY => 'Monthly'
    ];

    // TODO If we will have any new export type of statistic doc - we need to add it here and create a class in StatisticExport
    const AVAILABLE_STATISTIC_TYPES = [
        0 => [
            'class' => Invoices::class,
            'name'  => 'Invoice Report'
        ],
        1 => [
            'class' => ProjectReport::class,
            'name'  => 'Project Report'
        ],
    ];

    protected $fillable = [
        'name',
        'frequency',
        'sending_day',
        'document_type',
        'statistic_type',
    ];

    protected $casts = [
        'frequency' => 'int',
        'statistic_type' => 'int',
    ];

    protected $table = 'email_reports';

    public static function checkIsReportSendDay($day, $frequency): bool
    {
        if ($frequency === self::WEEKLY) {
            return Carbon::parse($day)->dayOfWeek === Carbon::today()->dayOfWeek;
        }
        if ($frequency === self::MONTHLY) {
            return (int)Carbon::parse($day)->format('d') === Carbon::today()->day;
        }
        return true;
    }

    public static function getDatesToWorkWith($frequency): array
    {
        if ($frequency === self::WEEKLY) {
            return [
                'startAt' => date('Y-m-d', strtotime('-1 week')),
                'endAt' => date('Y-m-d', strtotime('today'))
            ];
        }

        if ($frequency === self::MONTHLY) {
            return [
                'startAt' => date('Y-m-d', strtotime('-1 month')),
                'endAt' => date('Y-m-d', strtotime('today'))
            ];
        }

        return [
            'startAt' => date('Y-m-d', strtotime('-1 day')),
            'endAt' => date('Y-m-d', strtotime('today')),
        ];
    }

    public static function getDocumentType(EmailReports $emailReport): array
    {
        switch (trim(strtolower($emailReport->document_type))) {
            case 'csv':
                $type = Excel::CSV;
                $fsType = 'csv';
                break;
            case 'pdf':
                $type = Excel::MPDF;
                $fsType = Excel::MPDF;
                break;
            default:
                $type = Excel::XLSX;
                $fsType = 'xlsx';
        }

        return [
            'type' => $type,
            'fsType' => $fsType,
        ];
    }

    public static function getExporterClass($emailReport)
    {
        return self::AVAILABLE_STATISTIC_TYPES[$emailReport->statistic_type]['class'];

    }

    public function projects(): HasMany
    {
        return $this->hasMany(EmailReportsProjects::class, 'email_projects_id', 'id');
    }

    public function emails(): HasMany
    {
        return $this->hasMany(EmailReportsEmails::class, 'email_report_id', 'id');
    }
}
