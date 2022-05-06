<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SaveTaskEditHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Carbon $timestamp;
    private array $changes;
    private array $original;

    public function __construct(protected Task $task, protected User $author, array $changes = null, array $original = null)
    {
        $this->timestamp = now();
        $this->changes = $changes ?: $task->getChanges();
        $this->original = $original ?: $task->getOriginal();
    }

    public function handle(): void
    {
        foreach ($this->changes as $key => $value) {
            if (in_array($key, ['relative_position', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            TaskHistory::create([
                'task_id' => $this->task->id,
                'user_id' => $this->author->id,
                'field' => $key,
                'new_value' => $value,
                'old_value' => $this->original[$key],
            ])->updateQuietly(['created_at' => $this->timestamp]);
        }
    }
}
