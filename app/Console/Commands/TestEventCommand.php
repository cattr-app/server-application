<?php

namespace App\Console\Commands;

use App\Events\TestEvent;
use Illuminate\Console\Command;

class TestEventCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:test-disco';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Disco!';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('SOMEBODY ONCE TOLD ME...');
        event(new TestEvent());
    }
}
