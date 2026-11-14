<?php

namespace App\Models;

use App\Enums\OfflineScreenshotUploadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A validated offline screenshot archive that is attached to the intervals by a queue job.
 *
 * @property int $id
 * @property int $user_id Uploader
 * @property OfflineScreenshotUploadStatus $status
 * @property string|null $disk Disk of the stored archive, NULL after it was deleted
 * @property string|null $path
 * @property int $total Number of screenshots in the archive
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OfflineScreenshotUploadItem> $items
 */
class OfflineScreenshotUpload extends Model
{
    /** Directory of the stored archives on the default disk */
    public const ARCHIVE_DIRECTORY = 'offline-screenshots';

    protected $fillable = ['user_id', 'status', 'disk', 'path', 'total', 'finished_at'];

    protected $casts = [
        'user_id' => 'integer',
        'status' => OfflineScreenshotUploadStatus::class,
        'total' => 'integer',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfflineScreenshotUploadItem::class, 'upload_id');
    }

    /**
     * Deletes the stored archive; the row keeps no reference to it afterwards.
     */
    public function deleteArchive(): void
    {
        if ($this->path === null) {
            return;
        }

        Storage::disk($this->disk)->delete($this->path);
        $this->forceFill(['disk' => null, 'path' => null])->save();
    }

    /**
     * Marks the upload as finished without a complete result and deletes its archive.
     */
    public function markFailed(): void
    {
        $this->forceFill(['status' => OfflineScreenshotUploadStatus::FAILED, 'finished_at' => now()])->save();

        try {
            $this->deleteArchive();
        } catch (Throwable $e) {
            // The scheduled cattr:tmp:clean removes what is left
            report($e);
        }
    }
}
