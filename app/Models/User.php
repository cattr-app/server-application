<?php

namespace App\Models;

use App\Mail\ResetPassword;
use App\Scopes\UserAccessScope;
use App\Traits\HasRole;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Hash;
use Laravel\Sanctum\HasApiTokens;

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
 * @apiSuccess {String}   user.url                     `Not used`
 * @apiSuccess {Boolean}  user.computer_time_popup     `Not used`
 * @apiSuccess {Boolean}  user.blur_screenshots        `Not used`
 * @apiSuccess {Boolean}  user.web_and_app_monitoring  `Not used`
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
 * @apiParam {String}   [url]                     `Not used`
 * @apiParam {Boolean}  [computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [web_and_app_monitoring]  `Not used`
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
 * @apiParam {String}   [users.url]                     `Not used`
 * @apiParam {Boolean}  [users.computer_time_popup]     `Not used`
 * @apiParam {Boolean}  [users.blur_screenshots]        `Not used`
 * @apiParam {Boolean}  [users.web_and_app_monitoring]  `Not used`
 * @apiParam {String}   [users.user_language]            Language which is used for frontend translations and emails
 *
 * @apiVersion 1.0.0
 */


/**
 * App\Models\User
 *
 * @property int $id
 * @property string $full_name
 * @property string $email
 * @property string|null $url
 * @property int|null $company_id
 * @property string|null $avatar
 * @property int|null $screenshots_active
 * @property int|null $manual_time
 * @property int|null $computer_time_popup
 * @property bool|null $blur_screenshots
 * @property bool|null $web_and_app_monitoring
 * @property int|null $screenshots_interval
 * @property int $active
 * @property string $password
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $timezone
 * @property int $important
 * @property int $change_password
 * @property int $is_admin
 * @property int $role_id
 * @property string $user_language
 * @property string $type
 * @property bool $invitation_sent
 * @property int $nonce
 * @property int $client_installed
 * @property int $permanent_screenshots
 * @property \Illuminate\Support\Carbon $last_activity
 * @property-read bool $online
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|\App\Models\Project[] $projects
 * @property-read int|null $projects_count
 * @property-read Collection|\App\Models\ProjectsUsers[] $projectsRelation
 * @property-read int|null $projects_relation_count
 * @property-read Collection|\App\Models\Property[] $properties
 * @property-read int|null $properties_count
 * @property-read \App\Models\Role $role
 * @property-read Collection|\App\Models\Task[] $tasks
 * @property-read int|null $tasks_count
 * @property-read Collection|\App\Models\TimeInterval[] $timeIntervals
 * @property-read int|null $time_intervals_count
 * @property-read Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static EloquentBuilder|User active()
 * @method static EloquentBuilder|User admin()
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static EloquentBuilder|User newModelQuery()
 * @method static EloquentBuilder|User newQuery()
 * @method static QueryBuilder|User onlyTrashed()
 * @method static EloquentBuilder|User query()
 * @method static EloquentBuilder|User whereActive($value)
 * @method static EloquentBuilder|User whereAvatar($value)
 * @method static EloquentBuilder|User whereBlurScreenshots($value)
 * @method static EloquentBuilder|User whereChangePassword($value)
 * @method static EloquentBuilder|User whereClientInstalled($value)
 * @method static EloquentBuilder|User whereCompanyId($value)
 * @method static EloquentBuilder|User whereComputerTimePopup($value)
 * @method static EloquentBuilder|User whereCreatedAt($value)
 * @method static EloquentBuilder|User whereDeletedAt($value)
 * @method static EloquentBuilder|User whereEmail($value)
 * @method static EloquentBuilder|User whereFullName($value)
 * @method static EloquentBuilder|User whereId($value)
 * @method static EloquentBuilder|User whereImportant($value)
 * @method static EloquentBuilder|User whereInvitationSent($value)
 * @method static EloquentBuilder|User whereIsAdmin($value)
 * @method static EloquentBuilder|User whereLastActivity($value)
 * @method static EloquentBuilder|User whereManualTime($value)
 * @method static EloquentBuilder|User whereNonce($value)
 * @method static EloquentBuilder|User wherePassword($value)
 * @method static EloquentBuilder|User wherePermanentScreenshots($value)
 * @method static EloquentBuilder|User whereRoleId($value)
 * @method static EloquentBuilder|User whereScreenshotsActive($value)
 * @method static EloquentBuilder|User whereScreenshotsInterval($value)
 * @method static EloquentBuilder|User whereTimezone($value)
 * @method static EloquentBuilder|User whereType($value)
 * @method static EloquentBuilder|User whereUpdatedAt($value)
 * @method static EloquentBuilder|User whereUrl($value)
 * @method static EloquentBuilder|User whereUserLanguage($value)
 * @method static EloquentBuilder|User whereWebAndAppMonitoring($value)
 * @method static QueryBuilder|User withTrashed()
 * @method static QueryBuilder|User withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    use HasRole;
    use HasFactory;
    use HasApiTokens;

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
        'avatar',
        'screenshots_active',
        'manual_time',
        'computer_time_popup',
        'blur_screenshots',
        'web_and_app_monitoring',
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
        'invitation_sent',
        'nonce',
        'client_installed',
        'last_activity',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'full_name' => 'string',
        'email' => 'string',
        'url' => 'string',
        'company_id' => 'integer',
        'avatar' => 'string',
        'screenshots_active' => 'integer',
        'manual_time' => 'integer',
        'computer_time_popup' => 'integer',
        'blur_screenshots' => 'boolean',
        'web_and_app_monitoring' => 'boolean',
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
        'invitation_sent' => 'boolean',
        'nonce' => 'integer',
        'client_installed' => 'integer',
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
        'last_activity',
    ];

    protected $hidden = [
        'password',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new UserAccessScope);
    }

    protected $appends = [
        'online',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_users', 'user_id', 'project_id')
            ->withPivot('role_id');
    }

    public function projectsRelation(): HasMany
    {
        return $this->hasMany(ProjectsUsers::class, 'user_id', 'id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'tasks_users', 'user_id', 'task_id');
    }

    public function timeIntervals(): HasMany
    {
        return $this->hasMany(TimeInterval::class, 'user_id');
    }

    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'entity');
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

    protected function online(): Attribute
    {
        return Attribute::make(
            get: static fn($value, $attributes) => ($attributes['last_activity'] ?? false) &&
                Carbon::parse($attributes['last_activity'])->diffInSeconds(Carbon::now())
                < config('app.user_activity.online_status_time'),
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: static fn($value) => Hash::needsRehash($value) ? Hash::make($value) : $value,
        );
    }

    public function scopeAdmin(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('is_admin', true);
    }

    public function scopeActive(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('active', true);
    }
}
