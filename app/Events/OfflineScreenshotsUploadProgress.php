<?php

namespace App\Events;

use App\Models\OfflineScreenshotUpload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Progress or completion of the background processing of an offline screenshot archive, for the uploader. It carries
 * no results: the client fetches them from `GET offline-sync/uploads/{id}`, which also works without Reverb.
 */
class OfflineScreenshotsUploadProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public const CHANNEL = 'offline-sync';
    public const EVENT = 'offline-sync.upload';

    public function __construct(
        protected int $uploadId,
        protected int $userId,
        protected string $status,
        protected int $total,
        protected int $processed,
    ) {
    }

    public static function for(OfflineScreenshotUpload $upload, int $processed): self
    {
        return new self($upload->id, $upload->user_id, $upload->status->value, $upload->total, $processed);
    }

    public function broadcastAs(): string
    {
        return self::EVENT;
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->uploadId,
            'status' => $this->status,
            'total' => $this->total,
            'processed' => $this->processed,
        ];
    }

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(sprintf('%s.%s', self::CHANNEL, $this->userId))];
    }
}
