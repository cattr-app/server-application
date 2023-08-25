<?php

namespace App\Events;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChangeEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param Task|Project|TimeInterval $model */
    public function __construct(
        protected string $entityType,
        protected string $action,
        protected $model,
        protected int $userId
    ) {
        if ($model instanceof Task) {
            $model->load(['priority', 'project', 'users', 'status']);
        } elseif ($model instanceof Project) {
            // TODO
        } elseif ($model instanceof TimeInterval) {
            // TODO
        }
    }

    public function broadcastAs(): string
    {
        return sprintf('%s.%s', $this->entityType, $this->action);
    }

    public function broadcastWith(): array
    {
        return [
            'model' => $this->model->setPermissionsUser(User::query()->find($this->userId))->append('can')->makeVisible('can'),
        ];
    }

    /** @return Channel[] */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(sprintf('%s.%s', $this->entityType, $this->userId))];
    }

    /**
     * @param Task|Project|TimeInterval $model
     * @return int[]
     */
    public static function getRelatedUserIds($model): array
    {
        $userRelation = match (true) {
            $model instanceof Task => 'tasks',
            $model instanceof Project => 'projects',
            $model instanceof TimeInterval => 'timeIntervals',
            default => null,
        };

        $query = User::query()->where('role_id', Role::ADMIN->value)->orWhere('role_id', Role::MANAGER->value);
        if (isset($userRelation)) {
            $query = $query->orWhereHas($userRelation, fn (Builder $builder) => $builder->where('id', $model->id));
        }

        return $query->pluck('id')->toArray();
    }
}
