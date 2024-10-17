<?php

namespace App\Models;

use App\Enums\AttachmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Eloquent as EloquentIdeHelper;

/**
 * App\Models\Attachment
 *
 * @property string $id
 * @property int $attachmentable_id
 * @property string $attachmentable_type
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property string $hash
 * @property int $project_id
 * @property int $user_id
 * @property AttachmentStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $attachmentAbleRelation
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\User $user
 * @method static EloquentBuilder|Attachment newModelQuery()
 * @method static EloquentBuilder|Attachment newQuery()
 * @method static EloquentBuilder|Attachment query()
 * @method static EloquentBuilder|Attachment whereAttachmentableId($value)
 * @method static EloquentBuilder|Attachment whereAttachmentableType($value)
 * @method static EloquentBuilder|Attachment whereCreatedAt($value)
 * @method static EloquentBuilder|Attachment whereHash($value)
 * @method static EloquentBuilder|Attachment whereId($value)
 * @method static EloquentBuilder|Attachment whereMimeType($value)
 * @method static EloquentBuilder|Attachment whereOriginalName($value)
 * @method static EloquentBuilder|Attachment whereProjectId($value)
 * @method static EloquentBuilder|Attachment whereSize($value)
 * @method static EloquentBuilder|Attachment whereStatus($value)
 * @method static EloquentBuilder|Attachment whereUpdatedAt($value)
 * @method static EloquentBuilder|Attachment whereUserId($value)
 * @mixin EloquentIdeHelper
 */

class Attachment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'attachmentable_id',
        'attachmentable_type',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'hash',
        'project_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'attachmentable_id' => 'integer',
        'attachmentable_type' => 'string',
        'original_name' => 'string',
        'mime_type' => 'string',
        'extension' => 'string',
        'size' => 'integer',
        'hash' => 'string',
        'project_id' => 'integer',
        'user_id' => 'integer',
        'status' => AttachmentStatus::class,
    ];

    /**
     * It's important to name the relationship the same as the method because otherwise
     * eager loading of the polymorphic relationship will fail on queued jobs.
     *
     * @see https://github.com/laravelio/laravel.io/issues/350
     */
    public function attachmentAbleRelation(): MorphTo
    {
        return $this->morphTo('attachmentAbleRelation', 'attachmentable_type', 'attachmentable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
