<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Project;
use App\Models\Role;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    use SoftDeletes;


    /**
     * table name from database
     * @var string
     */
    protected $table = 'users';


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'full_name',
        'first_name',
        'last_name',
        'email',
        'url',
        'company_id',
        'level',
        'payroll_access',
        'billing_access',
        'avatar',
        'screenshots_active',
        'manual_time',
        'permanent_tasks',
        'computer_time_popup',
        'poor_time_popup',
        'blur_screenshots',
        'web_and_app_monitoring',
        'webcam_shots',
        'screenshots_interval',
        'user_role_value',
        'active',
        'password',
        'role_id',
    ];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    /**
     * The users that belong to the projects.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'projects_users', 'user_id', 'project_id');
    }
}
