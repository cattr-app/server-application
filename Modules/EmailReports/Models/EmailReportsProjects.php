<?php

namespace Modules\EmailReports\Models;

use App\Models\AbstractModel;
use App\Models\Project;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EmailReportsProjects
 * @package Modules\EmailReports\Models
 */
class EmailReportsProjects extends AbstractModel
{
    protected $fillable = [
        'email_projects_id',
        'project_id',
    ];

    protected $table = 'email_reports_projects';

    /**
     * @return BelongsTo
     */
    public function projects(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo
     */
    public function emailReports(): BelongsTo
    {
        return $this->belongsTo(EmailReports::class, 'email_projects_id');
    }
}
