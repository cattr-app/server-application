<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Class DemoReset
 */
class DemoReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr demo flush';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->call('down', [
            '--message' => 'Data reset'
        ]);

        $this->call('cattr:reset', [
            '--force' => true,
            '--seed' => true,
            '--images' => true
        ]);

        $this->call('cattr:demo:plan');
        $this->call('cattr:demo:emulate');

        $this->call('up');

        return 0;
    }
}
