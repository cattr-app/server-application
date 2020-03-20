<?php

namespace Modules\EmailReports\Models;

use Illuminate\Database\Eloquent\Model;

class EmailReportsEmails extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email_reports_id',
        'email',
    ];

    protected $table = 'email_reports_emails';
}
