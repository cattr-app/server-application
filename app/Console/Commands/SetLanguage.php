<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;
use Settings;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:set:language')]
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

        Settings::scope('core')->set('language', $language);

        $this->info(strtoupper($language) . ' language successfully set');
    }
}
