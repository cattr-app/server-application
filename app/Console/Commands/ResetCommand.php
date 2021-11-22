<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use DB;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Nwidart\Modules\Commands\SeedCommand as ModuleSeedCommand;
use Settings;
use Storage;

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

    protected array $protectedTables = ['migrations', 'jobs', 'failed_jobs'];

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): int
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
            Storage::deleteDirectory('uploads/screenshots');
        }

        $this->call(SeedCommand::class, [
            '--class' => 'InitialSeeder',
            '--force' => true
        ]);

        if ($this->option('seed')) {
            $this->call(SeedCommand::class);
            $this->call(ModuleSeedCommand::class);
        }

        Settings::scope('core')->set('installed', true);

        return 0;
    }
}
