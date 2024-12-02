<?php

namespace App\Console\Commands;

use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class CalculateEfficiency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:calculate-efficiency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate efficiency for users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = Carbon::now()->subMonth(2);
        $users = User::all();
        foreach ($users as $user) {
            $tasks = $user->tasks()
                ->select(['id', 'estimate'])
                ->whereHas('status', static function (Builder $query) {
                    $query->where('active', false);
                })
                ->whereHas('timeIntervals', static function (Builder $query) use ($startDate) {
                    $query
                        ->whereNotNull('estimate')
                        ->where('start_at', '>', $startDate);
                })
                ->get()
                ->keyBy('id')
                ->toArray();

            if (empty($tasks)) {
                $user->efficiency = null;
                $user->save();
                continue;
            }

            $taskIds = array_keys($tasks);
            $timeIntervals = TimeInterval::query()
                ->select(['user_id', 'task_id', DB::raw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) as duration')])
                ->where('start_at', '>', $startDate)
                ->where('user_id', $user->id)
                ->whereIn('task_id', $taskIds)
                ->groupBy(['user_id', 'task_id'])
                ->get();

            $totalEfficiency = 0;
            foreach ($timeIntervals as $timeInterval) {
                $tasks[$timeInterval->task_id]['duration'] = (int)$timeInterval->duration;
                $totalEfficiency += $tasks[$timeInterval->task_id]['duration'] / (float)$tasks[$timeInterval->task_id]['estimate'];
            }

            $user->efficiency = $totalEfficiency / count($tasks);
            $user->save();
        }
    }
}
