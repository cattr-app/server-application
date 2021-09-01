<?php

namespace App\Models;

use App\Scopes\ScreenshotScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\TrackedApplication
 *
 * @property int $id
 * @property string $title
 * @property string $executable
 * @property int|null $time_interval_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TimeInterval|null $timeInterval
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication newQuery()
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereExecutable($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereTimeIntervalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TrackedApplication whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TrackedApplication withoutTrashed()
 * @mixin \Eloquent
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

        static::addGlobalScope(new ScreenshotScope);
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
