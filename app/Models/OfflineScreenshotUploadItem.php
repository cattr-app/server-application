<?php

namespace App\Models;

use App\Scopes\TimeIntervalAccessScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The result of one entry of an offline screenshot archive.
 *
 * @property int $id
 * @property int $upload_id
 * @property string $entry_name
 * @property int $user_id Owner of the interval the screenshot belongs to
 * @property string $screenshot_id
 * @property int|null $interval_id NULL if no suitable interval was found
 * @property bool $success
 * @property string $message Translation key
 * @property-read TimeInterval|null $interval
 */
class OfflineScreenshotUploadItem extends Model
{
    protected $fillable = ['upload_id', 'entry_name', 'user_id', 'screenshot_id', 'interval_id', 'success', 'message'];

    protected $casts = [
        'upload_id' => 'integer',
        'user_id' => 'integer',
        'interval_id' => 'integer',
        'success' => 'boolean',
    ];

    /**
     * Only the uploader reads the results, and only intervals they could attach screenshots to are stored.
     */
    public function interval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'interval_id')->withoutGlobalScope(TimeIntervalAccessScope::class);
    }
}
