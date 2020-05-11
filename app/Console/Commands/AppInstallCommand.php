<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PDOException;
use RuntimeException;
use Illuminate\Support\Facades\Validator;

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


        $this->info("Welcome to Cattr installation wizard\n");
        $this->info("Let's connect to your database first");

        if ($this->settingUpDatabase() !== 0) {
            return -1;
        }

        $this->setUrls();

        $this->info('Enter administrator credentials:');
        $adminData = $this->askAdminCredentials();

        $this->settingUpEnvMigrateAndSeed();

        if (!$this->registerInstance($adminData['email'])) {
            // User did not confirm installation
            $this->call('migrate:reset');
            DB::statement("DROP TABLE migrations");

            $this->filesystem->delete(base_path('.env'));
            $this->callSilent('cache:clear');
            return -1;
        }

        $this->setLanguage();

        $this->info('Creating admin user');
        $this->call('cattr:make:admin', $adminData);

        $enableRecaptcha = $this->choice('Enable ReCaptcha 2', ['Yes', 'No'], 1) === 'Yes';
        $this->updateEnvData('RECAPTCHA_ENABLED', $enableRecaptcha ? 'true' : 'false');
        if ($enableRecaptcha) {
            $this->updateEnvData('RECAPTCHA_SITE_KEY', $this->ask('ReCaptcha 2 site key'));
            $this->updateEnvData('RECAPTCHA_SECRET_KEY', $this->ask('ReCaptcha 2 secret key'));
        }

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
            'name' => 'language'
        ], [
            'value' => $language
        ]);

        $this->info(strtoupper($language) . ' language successfully set');
    }

    protected function registerInstance(string $adminEmail): bool
    {
        return $this->call('cattr:register', [
            'adminEmail' => $adminEmail,
            '--i' => true
        ]);
    }

    protected function setUrls(): void
    {
        $appUrlIsValid = false;
        do {
            $appUrl = $this->ask('Full URL to backend (API) application (example: http://cattr.acme.corp/)');
            $appUrlIsValid = preg_match('/^https?:\/\//', $appUrl);
            if (!$appUrlIsValid) {
                $this->warn('URL should begin with http or https');
            }
        } while (!$appUrlIsValid);
        $this->updateEnvData('APP_URL', $appUrl);

        $frontendUrl = $this->ask('Trusted frontend domain (e.g. cattr.acme.corp). In most cases, this domain will be the same as the backend (API) one.');
        $frontendUrl = preg_replace('/^https?:\/\//', '', $frontendUrl);
        $frontendUrl = preg_replace('/\/$/', '', $frontendUrl);
        $this->updateEnvData('ALLOWED_ORIGINS',
            '"' . $frontendUrl . '"');
        $this->updateEnvData('FRONTEND_APP_URL',
            '"' . $frontendUrl . '"');
    }

    protected function askAdminCredentials(): array
    {
        do {
            $email = $this->ask('Admin E-Mail');

            $validator = Validator::make([
                'email' => $email
            ], [
                'email' => 'email'
            ]);

            $emailIsValid = !$validator->fails();

            if (!$emailIsValid) {
                $this->warn('Email is incorrect');
            }
        } while (!$emailIsValid);

        $password = $this->secret("Password");
        $name = $this->ask('Admin Full Name');

        return [
            'email' => $email,
            'password' => $password,
            'name' => $name,
            '--o' => true,
        ];
    }

    protected function settingUpEnvMigrateAndSeed(): void
    {
        $this->info('Setting up JWT secret key');
        $this->callSilent('jwt:secret', ['--force' => true]);

        $this->info('Running up migrations');
        $this->call('migrate');

        $this->info('Running required seeders');
        $this->call('db:seed', ['--class' => 'InitialSeeder']);

        $this->updateEnvData('APP_DEBUG', 'false');
    }

    protected function createDatabase(): void
    {
        $connectionName = config('database.default');
        $databaseName = config("database.connections.{$connectionName}.database");

        config(["database.connections.{$connectionName}.database" => null]);
        DB::purge();

        DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

        config(["database.connections.{$connectionName}.database" => $databaseName]);
        DB::purge();

        $this->info("Created database $databaseName.");
    }

    protected function settingUpDatabase(): int
    {
        $this->updateEnvData('DB_HOST', $this->ask('database host', 'localhost'));
        $this->updateEnvData('DB_PORT', $this->ask('database port', 3306));
        $this->updateEnvData('DB_USERNAME', $this->ask('database username', 'root'));
        $this->updateEnvData('DB_PASSWORD', $this->secret('database password'));
        $this->updateEnvData('DB_DATABASE', $this->ask('database name', 'app_cattr'));

        try {
            DB::connection()->getPdo();

            if (Schema::hasTable('migrations')) {
                throw new RuntimeException('Looks like the application was already installed. Please, make sure that database was flushed and then try again.');
            }
        } catch (PDOException $e) {
            if ($e->getCode() !== 1049) {
                $this->error($e->getMessage());

                return -1;
            }

            try {
                $this->createDatabase();
            } catch (Exception $e) {
                $this->error($e->getMessage());

                return -1;
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return -1;
        }

        $this->info("Database configuration successful.");

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
