<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Console\DownCommand;
use Illuminate\Foundation\Console\UpCommand;

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
        $this->call(DownCommand::class);

        $this->call(ResetCommand::class, [
            '--force' => true,
            '--seed' => true,
            '--images' => true
        ]);

        $this->call(PlanWork::class);
        $this->call(EmulateWork::class);

        $this->call(UpCommand::class);

        return 0;
    }
}
