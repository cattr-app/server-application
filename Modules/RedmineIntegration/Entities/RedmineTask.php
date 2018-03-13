<?php

namespace Modules\RedmineIntegration\Entities;

use Illuminate\Database\Eloquent\Model;

class RedmineTask extends Model
{
    protected $table = 'redmine_tasks';

    protected $fillable = ['task_id', 'redmine_task_id'];

    public function task()
    {
        return $this->belongsTo(Task::class, 'project_id');
    }
}
