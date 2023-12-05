<?php

namespace App\Models;

use App\Enums\Role;
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
use Laravel\Sanctum\PersonalAccessToken;

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
 * @property-read bool $can_view_team_tab
 * @property-read bool $can_create_task
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|Project[] $projects
 * @property-read int|null $projects_count
 * @property-read Collection|ProjectsUsers[] $projectsRelation
 * @property-read int|null $projects_relation_count
 * @property-read Collection|Property[] $properties
 * @property-read int|null $properties_count
 * @property-read Collection|Task[] $tasks
 * @property-read int|null $tasks_count
 * @property-read Collection|TimeInterval[] $timeIntervals
 * @property-read int|null $time_intervals_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static EloquentBuilder|User active()
 * @method static EloquentBuilder|User admin()
 * @method static UserFactory factory(...$parameters)
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
        'can_view_team_tab',
        'can_create_task',
    ];

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

    protected function canViewTeamTab(): Attribute
    {
        $self = $this;
        return Attribute::make(
            get: static fn() => $self->hasRole([Role::ADMIN, Role::MANAGER, Role::AUDITOR])
                || $self->hasRoleInAnyProject([Role::MANAGER, Role::AUDITOR]),
        );
    }

    protected function canCreateTask(): Attribute
    {
        $self = $this;
        return Attribute::make(
            get: static fn() => $self->hasRole([Role::ADMIN, Role::MANAGER])
                || $self->hasRoleInAnyProject(Role::MANAGER, Role::USER),
        );
    }

    public function scopeAdmin(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('role_is', \App\Enums\Role::ADMIN);
    }

    public function scopeActive(EloquentBuilder $query): EloquentBuilder
    {
        return $query->where('active', true);
    }
}
