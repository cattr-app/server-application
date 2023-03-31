<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\ViewTaskWorkers;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Settings;

class RecreateTaskWorkersView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:task:recreate-workers-view';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recreates materialized table for ViewTaskWorkers model';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $timezone = Settings::scope('core')->get('timezone');
        $reportCreatedAt = Carbon::now($timezone)->format('Y-m-d H:i:s e');

        Task::lazyById()->each(static function (Task $task) {
            ViewTaskWorkers::whereTaskId($task->id)->delete();

            ViewTaskWorkers::insertUsing(
                ['user_id', 'task_id', 'duration', 'created_by_cron'],
                DB::table('view_task_workers')
                    ->select(['user_id', 'task_id', 'duration', DB::raw("'1' as created_by_cron")])
                    ->where('task_id', '=', $task->id)
            );
        });

        Settings::scope('core.reports')->set('planned_time_report_date', $reportCreatedAt);

        ViewTaskWorkers::whereDoesntHave('task')
            ->orWhereDoesntHave('user')
            ->orWhereHas('task',
                static fn(EloquentBuilder $query) => $query
                    ->whereNotNull('deleted_at')
            )->delete();
    }
}
