<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeInterval;

class RemoveDuplicateIntervals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'amazing-time:remove-duplicate-intervals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes duplicates in the Time intervals table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $interval_ids = \DB::table('time_intervals as ti1')
            ->join('time_intervals as ti2', function ($join) {
                $join->on('ti2.start_at', '=', 'ti1.start_at');
                $join->on('ti2.end_at', '=', 'ti1.end_at');
                $join->on('ti2.user_id', '=', 'ti1.user_id');
                $join->on('ti2.id', '>', 'ti1.id');
            })
            ->whereNull('ti2.deleted_at')
            ->pluck('ti2.id')
            ->unique();

        TimeInterval::destroy($interval_ids->toArray());

        $count = $interval_ids->count();
        $this->info("Removed $count intervals.");
    }
}
