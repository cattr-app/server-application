<?php

namespace App\Console\Commands;

use App\Helpers\ModuleHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use JsonException;
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
     * @param Client $client
     *
     * @return int
     * @throws JsonException|GuzzleException
     */
    public function handle(Client $client): int
    {
        if (Settings::scope('core')->get('instance')) {
            echo 'Application already registered';
            return 1;
        }

        try {
            $appVersion = config('app.version');

            $response = $client->post(config('app.stats_collector_url') . '/instance', [
                'json' => [
                    'ownerEmail' => $this->argument('adminEmail'),
                    'version' => $appVersion,
                    'modules' => ModuleHelper::getModulesInfo(),
                    'image' => getenv('IMAGE_VERSION')
                ]
            ]);

            $responseBody = json_decode(
                $response->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
            );

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

            if ($responseBody['release']['vulnerable']) {
                if ($this->option('i')) {
                    // Interactive mode
                    return $this->confirm('You have a vulnerable version, are you sure you want to continue?');
                }

                $this->alert('You have a vulnerable version. Please update to the latest version.');
            }

            return 0;
        } catch (Exception $e) {
            if ($e->getResponse()) {
                $error = json_decode(
                    $e->getResponse()->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
                );
                $this->warn($error['message']);
            } else {
                $this->warn('Сould not get a response from the server to check the relevance of your version.');
            }

            return 0;
        }
    }
}
