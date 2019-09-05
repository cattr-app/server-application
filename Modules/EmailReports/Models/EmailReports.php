<?php


namespace Modules\EmailReports\Models;


use App\Models\AbstractModel;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailReports extends AbstractModel
{
    const DAILY = 0;
    const WEEKLY = 1;
    const MONTHLY = 2;

    protected $fillable = [
        'name',
        'email',
        'project_ids',
        'frequency',
        'value',
    ];

    protected $table = 'email_reports';

    public static function checkIsReportSendDay($day, $frequency)
    {
        // If day equals 0 - user selected last month day as a receiving reports day
        if ($day === 0) {
            return Carbon::today()->isLastOfMonth();
        }
        if ($frequency === self::WEEKLY) {
            return $day  === Carbon::today()->dayOfWeek;
        }
        if ($frequency === self::MONTHLY) {
            return $day == Carbon::today()->day;
        }

        return true;
    }

    public static function getDatesToWorkWith($frequency)
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
            'startAt' => date('Y-m-d', strtotime('today')),
            'endAt' => null
        ];
    }
}
