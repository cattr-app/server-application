<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    
	/**
     * table name from database
     * @var string
     */
    protected $table = 'tasks';


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
