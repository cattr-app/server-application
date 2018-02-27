<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Project extends Model
{
    
	/**
     * table name from database
     * @var string
     */
    protected $table = 'projects';

    protected $fillable = array('id', 'user_id', 'role');

    /**
     * The users that belong to the projects.
     */
    public function users()
    {
    	return $this->belongsToMany(User::class, 'projects_users', 'project_id', 'user_id');
    }

    /**
     * The tasks that belong to the project.
     */
    public function tasks()
    {
    	$this->hasMany(Task::class, 'project_id');
    }
    
}
