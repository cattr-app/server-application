<?php

namespace Modules\EmailReports\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailReportsEmails
 * @package Modules\EmailReports\Models
 */
class EmailReportsEmails extends Model
{
    protected $fillable = [
        'email_reports_id',
        'email',
    ];

    protected $table = 'email_reports_emails';

    public $timestamps = false;
}
