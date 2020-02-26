<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

class SetLanguage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:set:language {language}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets company language';

    public function handle(): void
    {
        $language = $this->argument('language');
        if (!in_array($language, config('app.languages'), true)) {
            $this->error('Invalid or not supported language');
            return;
        }

        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'language'], [
            'value' => $language
        ]);

        $this->info(strtoupper($language) . ' language successfully set');
    }
}
