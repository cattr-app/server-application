<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\SimpleCache\InvalidArgumentException;

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

        $this->call( 'cattr:reset', [
            '--force' => true,
            '--seed' => true,
            '--images' => true
        ] );

        $this->call('cattr:demo:plan');
        $this->call('cattr:demo:emulate');

        $this->call('cattr:set:language', [
            'language' => getenv('LANG')
        ]);
        $this->call('cattr:set:timezone', [
            'timezone' => getenv('TIMEZONE')
        ]);

        $this->call('up');

        return 0;
    }

}
