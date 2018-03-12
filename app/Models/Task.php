<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Task extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'tasks';
    protected $fillable = array('project_id', 'task_name', 'description', 'active', 'user_id', 'assigned_by', 'url');

    /**
     * The project that belong to the task.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }


    public function timeIntervals()
    {
    	$this->hasMany(TimeInterval::class, 'task_id');
    }
    
}
