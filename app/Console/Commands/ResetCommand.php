<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class ResetCommand
 */
class ResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:reset {--s|seed} {--f|force} {--i|images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr flush database';

    protected array $protectedFiles = ['uploads/screenshots/.gitignore', 'uploads/screenshots/thumbs/.gitignore'];
    protected array $protectedTables = ['migrations', 'jobs', 'failed_jobs'];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('Are you sure want to drop data for your Cattr instance?')) {
            return 0;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        foreach ($tables as $table) {
            if (!in_array($table, $this->protectedTables, true)) {
                DB::table($table)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        if ($this->option('images')) {
            $files = array_diff(Storage::allFiles('uploads/screenshots'), $this->protectedFiles);

            Storage::delete($files);
        }

        $this->call('db:seed', [
            '--class' => 'InitialSeeder'
        ]);

        if ($this->option('seed')) {
            $this->call('db:seed');
            $this->call('module:seed');
        }

        return 0;
    }
}
