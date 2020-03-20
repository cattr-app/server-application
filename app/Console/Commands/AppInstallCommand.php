<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\User;
use DB;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use MCStreetguy\ComposerParser\Factory as ComposerParser;
use RuntimeException;

class AppInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr Basic Installation';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(
        Filesystem $filesystem
    ) {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->filesystem->exists($this->laravel->environmentFilePath())) {
            $this->filesystem->copy(base_path('.env.example'), $this->laravel->environmentFilePath());
        }

        try {
            DB::connection()->getPdo();

            if (Schema::hasTable('migrations')) {
                $this->error('Looks like the application was already installed. Please, make sure that database was flushed then try again');

                return -1;
            }
        } catch (Exception $e) {
            // If we can't connect to the database that means that we're probably installing the app for the first time
        }


        $this->info("Welcome to CATTR installation wizard. First of, let's setup our application\n");
        $this->info('For now, we will setup the .env file configuration. You can change it later at any time.');
        $this->info("Let's connect to your database first");

        if ($this->settingUpDatabase() !== 0) {
            return -1;
        }

        $this->info('Enter administrator credentials:');
        $adminData = $this->askAdminCredentials();

        if (!$this->registerInstance($adminData['login'])) {
            // User did not confirm installation
            $this->filesystem->delete(base_path('.env'));
            return -1;
        }

        $this->settingUpEnvMigrateAndSeed();

        $this->setLanguage();
        $this->setTimeZone();

        $this->info('Creating admin user');
        $admin = $this->createAdminUser($adminData);
        $this->info("Administrator with email {$admin->email} was created successfully");

        $this->updateEnvData('RECAPTCHA_ENABLED', $this->choice('Enable RECaptcha', [
            'true' => 'Yes',
            'false' => 'No'
        ], 'false'));

        $this->call('config:cache');

        $this->info('Application was installed successfully!');
        return 0;
    }

    public function setLanguage(): void
    {
        $language = $this->choice('Choose default language', config('app.languages'), 0);

        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'language'], [
            'value' => $language
        ]);

        $this->info(strtoupper($language) . ' language successfully set');
    }

    public function setTimeZone(): void
    {
        Property::updateOrCreate([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'timezone'], [
            'value' => 'UTC'
        ]);

        $this->info('Default time zone set to UTC');
    }

    protected function registerInstance(string $adminEmail): bool
    {
        try {
            $client = new Client();

            $composerJson = ComposerParser::parse(base_path('composer.json'));
            $appVersion = $composerJson->getVersion();

            $response = $client->post('https://stats.cattr.app/v1/register', [
                'json' => [
                    'ownerEmail' => $adminEmail,
                    'version' => $appVersion
                ]
            ]);

            $responseBody = json_decode($response->getBody()->getContents(), true);

            if (isset($responseBody['flashMessage'])) {
                $this->info($responseBody['flashMessage']);
            }

            if (isset($responseBody['updateVersion'])) {
                $this->alert("New version is available: {$responseBody['updateVersion']}");
            }

            if ($responseBody['knownVulnerable']) {
                return $this->confirm('You have a vulnerable version, are you sure you want to continue?');
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

    protected function createAdminUser(array $admin): User
    {
        return User::create([
            'full_name' => $admin['name'],
            'email' => $admin['login'],
            'url' => '',
            'company_id' => 1,
            'payroll_access' => 1,
            'billing_access' => 1,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'permanent_tasks' => 0,
            'computer_time_popup' => 300,
            'poor_time_popup' => '',
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'webcam_shots' => 0,
            'screenshots_interval' => 9,
            'active' => true,
            'password' => $admin['password'],
            'is_admin' => true,
            'role_id' => 2,
        ]);
    }

    protected function askAdminCredentials(): array
    {
        $login = $this->ask('Admin E-Mail');
        $password = Hash::make($this->secret("Admin ($login) Password"));
        $name = $this->ask('Admin Full Name');

        return [
            'login' => $login,
            'password' => $password,
            'name' => $name,
        ];
    }

    protected function settingUpEnvMigrateAndSeed(): void
    {
        $this->updateEnvData('APP_URL',
            $this->ask('API endpoint FULL URL (https://api.example.com)'));

        $frontendUrl = $this->ask('Frontend application URL (https://example.com)');
        $this->updateEnvData('ALLOWED_ORIGINS',
            '"' . $frontendUrl . '"');
        $this->updateEnvData('FRONTEND_APP_URL',
            '"' . $frontendUrl . '"');

        $this->info('Setting up JWT secret key');
        $this->call('jwt:secret');

        $this->info('Running up migrations');
        $this->call('migrate');

        $this->info('Setting up default system roles');
        $this->call('db:seed', ['--class' => 'RoleSeeder']);

        $this->info('Switching off debug mode...');
        $this->updateEnvData('APP_DEBUG', 'false');
    }

    protected function settingUpDatabase(): int
    {
        $this->updateEnvData('DB_HOST', $this->ask('CATTR database host', 'localhost'));
        $this->updateEnvData('DB_PORT', $this->ask('CATTR database port', 3306));
        $this->updateEnvData('DB_USERNAME', $this->ask('CATTR database username', 'root'));
        $this->updateEnvData('DB_DATABASE', $this->ask('CATTR database name', 'app_cattr'));
        $this->updateEnvData('DB_PASSWORD', $this->secret('CATTR database password'));

        $this->call('config:cache');

        $this->info('Testing database connection...');
        try {
            DB::connection()->getPdo();

            if (Schema::hasTable('migrations')) {
                throw new RuntimeException('Looks like the application was already installed. Please, make sure that database was flushed and then try again.');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return -1;
        }
        $this->info("Database testing successfully.\n");

        return 0;
    }

    /**
     * @param string $key
     * @param  $value
     *
     * @return void
     */
    protected function updateEnvData(string $key, $value): void
    {
        file_put_contents($this->laravel->environmentFilePath(), preg_replace(
            $this->replacementPattern($key, $value),
            $key . '=' . $value,
            file_get_contents($this->laravel->environmentFilePath())
        ));
        Config::set($key, $value);
    }

    /**
     * Get a regex pattern that will match env APP_KEY with any random key.
     *
     * @param string $key
     * @param  $value
     *
     * @return string
     */
    protected function replacementPattern(string $key, $value): string
    {
        $escaped = preg_quote('=' . env($key), '/');

        return "/^{$key}=.*/m";
    }
}
