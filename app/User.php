<?php

namespace App;

use App\Mail\ResetPassword;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\Property;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Contracts\Auth\CanResetPassword;

/**
 * Class User
 * @package App
 *
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property string $url
 * @property int $company_id
 * @property string $level
 * @property int $payroll_access
 * @property int $billing_access
 * @property string $avatar
 * @property int $screenshots_active
 * @property int $manual_time
 * @property int $permanent_tasks
 * @property int $computer_time_popup
 * @property string $poor_time_popup
 * @property int $blur_screenshots
 * @property int $web_and_app_monitoring
 * @property int $webcam_shots
 * @property int $screenshots_interval
 * @property string $user_role_value
 * @property int $active
 * @property string $password
 * @property string $timezone
 * @property int $role_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 *
 * @property Role           $role
 * @property Project[]      $projects
 * @property Task[]         $tasks
 * @property TimeInterval[] $timeIntervals
 * @property User[]         $attached_users
 */
class User extends Authenticatable implements JWTSubject, CanResetPassword
{
    use Notifiable, SoftDeletes;

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
        'timezone',
        'role_id',
        'important',
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

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_users', 'user_id', 'project_id');
    }

    /**
     * @return BelongsToMany
     */
    public function attached_users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'relations_users', 'user_id', 'attached_user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function attached_to(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'relations_users', 'attached_user_id', 'user_id');
    }

    /**
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function timeIntervals(): HasMany
    {
        return $this->hasMany(TimeInterval::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::USER_CODE);
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($this->email, $token));
    }
}
