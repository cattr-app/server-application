<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

/**
 * Class SetTimeZone
 */
class SetTimeZone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:set:timezone {timezone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets company timezone';

    public function handle(): void
    {
        $timezone = $this->argument('timezone');
        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $this->error('Invalid time zone format');
            return;
        }

        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'timezone'], [
            'value' => $timezone
        ]);

        $this->info("$timezone time zone successfully set");
    }
}
