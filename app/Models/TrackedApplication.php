<?php

namespace App\Models;

use App\Scopes\TimeIntervalAccessScope;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * App\Models\TrackedApplication
 *
 * @property int $id
 * @property string $title
 * @property string $executable
 * @property int|null $time_interval_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int|null $user_id
 * @property-read TimeInterval|null $timeInterval
 * @property-read User|null $user
 * @method static Builder|TrackedApplication newModelQuery()
 * @method static Builder|TrackedApplication newQuery()
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication onlyTrashed()
 * @method static Builder|TrackedApplication query()
 * @method static Builder|TrackedApplication whereCreatedAt($value)
 * @method static Builder|TrackedApplication whereDeletedAt($value)
 * @method static Builder|TrackedApplication whereExecutable($value)
 * @method static Builder|TrackedApplication whereId($value)
 * @method static Builder|TrackedApplication whereTimeIntervalId($value)
 * @method static Builder|TrackedApplication whereTitle($value)
 * @method static Builder|TrackedApplication whereUpdatedAt($value)
 * @method static Builder|TrackedApplication whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication withoutTrashed()
 * @mixin Eloquent
 */
class TrackedApplication extends Model
{
    use SoftDeletes;

    protected $table = 'tracked_applications';

    protected $fillable = [
        'title',
        'executable',
        'user_id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new TimeIntervalAccessScope);
    }

    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
