<?php

namespace App\Console\Commands;

use App\Models\Property;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use MCStreetguy\ComposerParser\Factory as ComposerParser;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param Client $client
     * @param ComposerParser $composerParser
     * @return bool
     */
    public function handle(Client $client, ComposerParser $composerParser)
    {
        try {
            $composerJson = $composerParser::parse(base_path('composer.json'));
            $appVersion = $composerJson->getVersion();

            $response = $client->post('https://stats.cattr.app/v1/register', [
                'json' => [
                    'ownerEmail' => $this->argument('adminEmail'),
                    'version' => $appVersion
                ]
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['instanceId'])) {
                Property::updateOrCreate([
                    'entity_type' => Property::APP_CODE,
                    'entity_id' => 0,
                    'name' => 'INSTANCE_ID',
                    'value' => $responseBody['instanceId']
                ]);
            }

            if (isset($responseBody['flashMessage'])) {
                $this->info($responseBody['flashMessage']);
            }

            if (isset($responseBody['updateVersion'])) {
                $this->alert("New version is available: {$responseBody['updateVersion']}");
            }

            if ($responseBody['knownVulnerable']) {
                if ($this->option('i')) {
                    // Interactive mode
                    return $this->confirm('You have a vulnerable version, are you sure you want to continue?');
                } else {
                    $this->alert('You have a vulnerable version. Please update to the latest version.');
                }
            }

            return true;
        } catch (GuzzleException $e) {
            if ($e->getResponse()) {
                $error = json_decode($e->getResponse()->getBody(), true);
                $this->warn($error['message']);
            } else {
                $this->warn('Сould not get a response from the server to check the relevance of your version.');
            }

            return true;
        }
    }
}
