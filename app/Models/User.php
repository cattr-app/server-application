<?php

namespace App\Models;

use App\Mail\ResetPassword;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @apiDefine UserObject
 *
 * @apiSuccess {Integer}  user.id                       ID
 * @apiSuccess {String}   user.full_name                Name
 * @apiSuccess {String}   user.email                    Email
 * @apiSuccess {Integer}  user.company_id               Company ID
 * @apiSuccess {String}   user.avatar                   Avatar image url
 * @apiSuccess {Boolean}  user.screenshots_active       Should screenshots be captured
 * @apiSuccess {Boolean}  user.manual_time              Allow manual time edit
 * @apiSuccess {Integer}  user.screenshots_interval     Screenshots capture interval (seconds)
 * @apiSuccess {Boolean}  user.active                   Indicates active user when `TRUE`
 * @apiSuccess {String}   user.timezone                 User's timezone
 * @apiSuccess {ISO8601}  user.created_at               Creation DateTime
 * @apiSuccess {ISO8601}  user.updated_at               Update DateTime
 * @apiSuccess {ISO8601}  user.deleted_at               Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {Boolean}  user.payroll_access          `Not used`
 * @apiSuccess {Boolean}  user.billing_access          `Not used`
 * @apiSuccess {String}   user.url                     `Not used`
 * @apiSuccess {Boolean}  user.permanent_tasks         `Not used`
 * @apiSuccess {Boolean}  user.computer_time_popup     `Not used`
 * @apiSuccess {Boolean}  user.poor_time_popup         `Not used`
 * @apiSuccess {Boolean}  user.blur_screenshots        `Not used`
 * @apiSuccess {Boolean}  user.web_and_app_monitoring  `Not used`
 * @apiSuccess {Boolean}  user.webcam_shots            `Not used`
 * @apiSuccess {String}   user.user_language            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine UserParams
 *
 * @apiParam {Integer}  [id]                       ID
 * @apiParam {String}   [full_name]                Name
 * @apiParam {String}   [email]                    Email
 * @apiParam {Integer}  [company_id]               Company ID
 * @apiParam {String}   [avatar]                   Avatar image url
 * @apiParam {Boolean}  [screenshots_active]       Should screenshots be captured
 * @apiParam {Boolean}  [manual_time]              Allow manual time edit
 * @apiParam {Integer}  [screenshots_interval]     Screenshots capture interval (seconds)
 * @apiParam {Boolean}  [active]                   Indicates active user when `TRUE`
 * @apiParam {String}   [timezone]                 User's timezone
 * @apiParam {ISO8601}  [created_at]               Creation DateTime
 * @apiParam {ISO8601}  [updated_at]               Update DateTime
 * @apiParam {ISO8601}  [deleted_at]               Delete DateTime
 * @apiParam {Boolean}  [payroll_access]          `Not used`
 * @apiParam {Boolean}  [billing_access]          `Not used`
 * @apiParam {String}   [url]                     `Not used`
 * @apiParam {Boolean}  [permanent_tasks]         `Not used`
 * @apiParam {Boolean}  [computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [poor_time_popup]         `Not used`
 * @apiParam {Boolean}  [blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [web_and_app_monitoring]  `Not used`
 * @apiParam {Boolean}  [webcam_shots]            `Not used`
 * @apiParam {String}   [user_language]            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine UserScopedParams
 *
 * @apiParam {Integer}  [users.id]                       ID
 * @apiParam {String}   [users.full_name]                Name
 * @apiParam {String}   [users.email]                    Email
 * @apiParam {Integer}  [users.company_id]               Company ID
 * @apiParam {String}   [users.avatar]                   Avatar image url
 * @apiParam {Boolean}  [users.screenshots_active]       Should screenshots be captured
 * @apiParam {Boolean}  [users.manual_time]              Allow manual time edit
 * @apiParam {Integer}  [users.screenshots_interval]     Screenshots capture interval (seconds)
 * @apiParam {Boolean}  [users.active]                   Indicates active user when `TRUE`
 * @apiParam {String}   [users.timezone]                 User's timezone
 * @apiParam {ISO8601}  [users.created_at]               Creation DateTime
 * @apiParam {ISO8601}  [users.updated_at]               Update DateTime
 * @apiParam {ISO8601}  [users.deleted_at]               Delete DateTime
 * @apiParam {Boolean}  [users.payroll_access]          `Not used`
 * @apiParam {Boolean}  [users.billing_access]          `Not used`
 * @apiParam {String}   [users.url]                     `Not used`
 * @apiParam {Boolean}  [users.permanent_tasks]         `Not used`
 * @apiParam {Boolean}  [users.computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [users.poor_time_popup]         `Not used`
 * @apiParam {Boolean}  [users.blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [users.web_and_app_monitoring]  `Not used`
 * @apiParam {Boolean}  [users.webcam_shots]            `Not used`
 * @apiParam {String}   [users.user_language]            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */


/**
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
 * @property int $active
 * @property string $password
 * @property string $timezone
 * @property string $user_language
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $is_admin
 * @property bool $important
 * @property int $role_id
 * @property Project[]|Collection $projects
 * @property Task[]|Collection $tasks
 * @property TimeInterval[]|Collection $timeIntervals
 * @property Role $role
 * @property string|null $remember_token
 * @property int $change_password
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read Collection|ProjectsUsers[] $projectsRelation
 * @property-read Collection|Property[] $properties
 * @property-read Collection|Token[] $tokens
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|User onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|User whereActive($value)
 * @method static EloquentBuilder|User whereAvatar($value)
 * @method static EloquentBuilder|User whereBillingAccess($value)
 * @method static EloquentBuilder|User whereBlurScreenshots($value)
 * @method static EloquentBuilder|User whereChangePassword($value)
 * @method static EloquentBuilder|User whereCompanyId($value)
 * @method static EloquentBuilder|User whereComputerTimePopup($value)
 * @method static EloquentBuilder|User whereCreatedAt($value)
 * @method static EloquentBuilder|User whereDeletedAt($value)
 * @method static EloquentBuilder|User whereEmail($value)
 * @method static EloquentBuilder|User whereFullName($value)
 * @method static EloquentBuilder|User whereId($value)
 * @method static EloquentBuilder|User whereImportant($value)
 * @method static EloquentBuilder|User whereManualTime($value)
 * @method static EloquentBuilder|User wherePassword($value)
 * @method static EloquentBuilder|User wherePayrollAccess($value)
 * @method static EloquentBuilder|User wherePermanentTasks($value)
 * @method static EloquentBuilder|User wherePoorTimePopup($value)
 * @method static EloquentBuilder|User whereRememberToken($value)
 * @method static EloquentBuilder|User whereScreenshotsActive($value)
 * @method static EloquentBuilder|User whereScreenshotsInterval($value)
 * @method static EloquentBuilder|User whereTimezone($value)
 * @method static EloquentBuilder|User whereUpdatedAt($value)
 * @method static EloquentBuilder|User whereUrl($value)
 * @method static EloquentBuilder|User whereWebAndAppMonitoring($value)
 * @method static EloquentBuilder|User whereWebcamShots($value)
 * @method static QueryBuilder|User withTrashed()
 * @method static QueryBuilder|User withoutTrashed()
 * @mixin EloquentIdeHelper
 */
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
     * @var array
     */
    protected $with = [
        'role',
        'projectsRelation.role',
    ];

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
        'active',
        'password',
        'timezone',
        'important',
        'change_password',
        'role_id',
        'is_admin',
        'user_language',
        'type',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'full_name' => 'string',
        'email' => 'string',
        'url' => 'string',
        'company_id' => 'integer',
        'payroll_access' => 'boolean',
        'billing_access' => 'boolean',
        'avatar' => 'string',
        'screenshots_active' => 'integer',
        'manual_time' => 'integer',
        'permanent_tasks' => 'boolean',
        'computer_time_popup' => 'integer',
        'poor_time_popup' => 'integer',
        'blur_screenshots' => 'boolean',
        'web_and_app_monitoring' => 'boolean',
        'webcam_shots' => 'boolean',
        'screenshots_interval' => 'integer',
        'active' => 'integer',
        'password' => 'string',
        'timezone' => 'string',
        'important' => 'integer',
        'change_password' => 'int',
        'is_admin' => 'integer',
        'role_id' => 'integer',
        'user_language' => 'string',
        'type' => 'string',
    ];


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

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
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_users', 'user_id', 'project_id')
            ->withPivot('role_id');
    }

    /**
     * @param string $tokenString
     * @return Token
     */
    public function addToken(string $tokenString): Token
    {
        $tokenExpires = date('Y-m-d H:i:s', time() + 60 * auth()->factory()->getTTL());
        /** @var Token $token */
        $token = $this->tokens()->create(['token' => $tokenString, 'expires_at' => $tokenExpires]);
        return $token;
    }

    /**
     * @return HasMany
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    /**
     * @param string $token
     */
    public function invalidateToken(string $token)
    {
        $this->tokens()->where('token', $token)->delete();
    }

    /**
     * @param string|null $except
     */
    public function invalidateAllTokens(?string $except = null)
    {
        $this->tokens()->where('token', '!=', $except)->delete();
    }

    /**
     * @return HasMany
     */
    public function projectsRelation(): HasMany
    {
        return $this->hasMany(ProjectsUsers::class, 'user_id', 'id');
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
        return $this->hasMany(Property::class, 'entity_id')
            ->where('entity_type', Property::USER_CODE);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($this->email, $token));
    }

    /**
     * @param string $object
     * @param string $action
     * @param string|null $id
     * @return bool
     */
    public function allowed(string $object, string $action, $id = null): bool
    {
        return Role::can($this, $object, $action, $id);
    }

    /**
     * Set the user's password.
     *
     * @param $value
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = Hash::make($value);
    }
}
