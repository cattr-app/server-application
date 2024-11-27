<?php

namespace App\Events;

use App\Enums\Role;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TimeInterval;
use App\Models\User;
use App\Reports\DashboardExport;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Settings;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Builder as AdjacencyListBuilder;

class ChangeEvent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param Task|Project|TimeInterval $model */
    public function __construct(
        protected string $entityType,
        protected string $action,
        protected $model,
        protected int    $userId
    ) {
    }

    public function broadcastAs(): string
    {
        return sprintf('%s.%s', $this->entityType, $this->action);
    }

    public function broadcastWith(): array
    {
        // TODO: [ ] optimize loaded changes - payload too big
        $model = match (true) {
            $this->model instanceof Project && $this->entityType === 'gantt' => $this
                ->model
                ->setPermissionsUser(User::query()->find($this->userId))
                ->load([
                    'tasks' => fn(HasMany $queue) => $queue
                        ->orderBy('start_date')
                        ->select([
                            'id',
                            'task_name',
                            'priority_id',
                            'status_id',
                            'estimate',
                            'start_date',
                            'due_date',
                            'project_phase_id',
                            'project_id'
                        ])->with(['status', 'priority'])
                        ->withSum(['workers as total_spent_time'], 'duration')
                        ->withSum(['workers as total_offset'], 'offset')
                        ->withCasts(['start_date' => 'string', 'due_date' => 'string'])
                        ->whereNotNull('start_date')->whereNotNull('due_date'),
                    'phases' => fn(HasMany $queue) => $queue
                        ->select(['id', 'name', 'project_id'])
                        ->withMin([
                            'tasks as start_date' => fn(AdjacencyListBuilder $q) => $q
                                ->whereNotNull('start_date')
                                ->whereNotNull('due_date')
                        ], 'start_date')
                        ->withMax([
                            'tasks as due_date' => fn(AdjacencyListBuilder $q) => $q
                                ->whereNotNull('start_date')
                                ->whereNotNull('due_date')
                        ], 'due_date'),
                ])
                ->append('tasks_relations'),
            $this->model instanceof Task => $this->model->setPermissionsUser(User::query()->find($this->userId))
                ->load([
                    'priority',
                    'project',
                    'users',
                    'status',
                    'parents',
                    'children',
                    'phase:id,name',
                    'workers',
                    'workers.user:id,full_name',
                    'attachmentsRelation',
                    'attachmentsRelation.user:id,full_name',
                ])
                ->append(['can'])
                ->loadSum('workers as total_spent_time', 'duration')
                ->loadSum('workers as total_offset', 'offset')
                ->makeVisible('can'),
            $this->model instanceof TaskComment => $this->model
                ->load(
                    'user',
                    'attachmentsRelation',
                    'attachmentsRelation.user:id,full_name'
                ),
            $this->model instanceof Project => $this->model->setPermissionsUser(User::query()->find($this->userId))
                ->load([
                    'users',
                    'defaultPriority',
                    'statuses',
                    'workers',
                    'workers.user:id,full_name',
                    'workers.task:id,task_name',
                    'phases' => fn($q) => $q->withCount('tasks'),
                    'group',
                ])
                ->loadCount('tasks')
                ->append(['can'])
                ->loadSum('workers as total_spent_time', 'duration')
                ->loadSum('workers as total_offset', 'offset')
                ->makeVisible('can'),
            // Format a time interval as in the dashboard report
            $this->model instanceof TimeInterval => DashboardExport::init(
                [$this->model->user_id],
                [$this->model->task->project_id],
                Carbon::parse($this->model->start_at)->startOfDay(),
                Carbon::parse($this->model->end_at)->endOfDay(),
                Settings::scope('core')->get('timezone', 'UTC'),
                $this->model->user->timezone ?? Settings::scope('core')->get('timezone', 'UTC'),
            )->collection(['time_intervals.id' => $this->model->id])->first()->first()->toArray(),
            default => $this->model,
        };

        return ['model' => $model];
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
            $query = $query->orWhereHas($userRelation, fn(Builder $builder) => $builder->where('id', $model->id));
        }

        return $query->pluck('id')->toArray();
    }
}
