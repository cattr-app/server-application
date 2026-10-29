<?php

namespace App\Console\Commands;

use App\Helpers\ModuleHelper;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Exception;
use Settings;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:register')]
class RegisterInstance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:register {adminEmail} {--i : Interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send instance data on the statistics server';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (Settings::scope('core')->get('instance')) {
            echo 'Application already registered';
            return 1;
        }

        try {
            $appVersion = config('app.version');

            $responseBody = Http::acceptJson()
                ->post(config('app.stats_collector_url') . '/v2/instance', [
                    'ownerEmail' => $this->argument('adminEmail'),
                    'version' => $appVersion,
                    'modules' => ModuleHelper::getModulesInfo(),
                    'image' => getenv('IMAGE_VERSION'),
                ])
                ->throw()
                ->json();

            if (!is_array($responseBody)) {
                $this->warn('Could not parse the registration response from the server.');
                return 0;
            }

            if (isset($responseBody['instanceId'])) {
                Settings::scope('core')->set('instance', $responseBody['instanceId']);
            }

            if (isset($responseBody['release']['flashMessage'])) {
                $this->info($responseBody['release']['flashMessage']);
            }

            if (isset($responseBody['release']['lastVersion'])
                && $responseBody['release']['lastVersion'] > $appVersion
            ) {
                $this->alert("New version is available: {$responseBody['release']['lastVersion']}");
            }

            if ($responseBody['release']['vulnerable'] ?? false) {
                if ($this->option('i')) {
                    // Interactive mode
                    return $this->confirm('You have a vulnerable version, are you sure you want to continue?');
                }

                $this->alert('You have a vulnerable version. Please update to the latest version.');
            }

            return 0;
        } catch (RequestException $e) {
            $message = $e->response->json('message');
            $this->warn(is_string($message) && trim($message) !== '' ? $message : $e->getMessage());

            return 0;
        } catch (Exception) {
            $this->warn('Could not get a response from the server to check the relevance of your version.');

            return 0;
        }
    }
}
