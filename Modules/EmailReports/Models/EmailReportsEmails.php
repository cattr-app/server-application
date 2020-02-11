<?php

namespace Modules\EmailReports\Models;

use App\Models\AbstractModel;

/**
 * Class EmailReportsEmails
 * @package Modules\EmailReports\Models
 */
class EmailReportsEmails extends AbstractModel
{
    protected $fillable = [
        'email_reports_id',
        'email',
    ];

    protected $table = 'email_reports_emails';

    public $timestamps = false;
}
