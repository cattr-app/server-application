<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TimeIntervalFlush extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timeinterval:flush';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush cache table with time interval durations';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        DB::unprepared('CALL time_durations_cache_refresh()');
    }
}
