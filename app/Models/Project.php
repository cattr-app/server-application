<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'projects';

    protected $fillable = array('company_id', 'name', 'description');

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
