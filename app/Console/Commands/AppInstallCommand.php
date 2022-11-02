<?php

namespace App\Console\Commands;

use App\Helpers\EnvUpdater;
use DB;
use Exception;
use Illuminate\Cache\Console\ClearCommand;
use Illuminate\Console\Command;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Foundation\Console\KeyGenerateCommand;
use Illuminate\Support\Facades\Schema;
use PDOException;
use RuntimeException;
use Settings;
use Validator;

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

    public function __construct(
        protected Filesystem $filesystem
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        define('VIA_DOCKER', !!env('IMAGE_VERSION', false));
        if (!$this->filesystem->exists($this->laravel->environmentFilePath())) {
            $this->filesystem->copy(base_path('.env.example'), $this->laravel->environmentFilePath());
        }

        if (VIA_DOCKER == false) {
            try {
                DB::connection()->getPdo();

                if (Schema::hasTable('migrations')) {
                    $this->error('Looks like the application was already installed. '
                        . 'Please, make sure that database was flushed then try again');

                    return -1;
                }
            } catch (Exception) {
                // If we can't connect to the database that means that we're probably installing the app for the first time
            }
        }


        $this->info("Welcome to Cattr installation wizard\n");
        $this->info("Let's connect to your database first");

        if (VIA_DOCKER == false && $this->settingUpDatabase() !== 0) {
            return -1;
        }

        $this->setUrls();

        $this->info('Enter administrator credentials:');
        $adminData = $this->askAdminCredentials();

        $this->settingUpEnvMigrateAndSeed();

        if (!$this->registerInstance($adminData['email'])) {
            // User did not confirm installation
            $this->call(\Illuminate\Database\Console\Migrations\ResetCommand::class);
            DB::statement('DROP TABLE migrations;');

            $this->filesystem->delete(base_path('.env'));
            $this->callSilent(ClearCommand::class);
            return -1;
        }

        $this->setLanguage();

        $this->info('Creating admin user');
        $this->call(MakeAdmin::class, $adminData);

        $enableRecaptcha = $this->choice('Enable ReCaptcha 2', ['Yes', 'No'], 1) === 'Yes';
        EnvUpdater::set('RECAPTCHA_ENABLED', $enableRecaptcha ? 'true' : 'false');
        if ($enableRecaptcha) {
            EnvUpdater::bulkSet([
                'RECAPTCHA_SITE_KEY' => $this->ask('ReCaptcha 2 site key'),
                'RECAPTCHA_SECRET_KEY' => $this->ask('ReCaptcha 2 secret key'),
            ]);
        }

        $this->call(ConfigCacheCommand::class);
        Settings::scope('core')->set('installed', true);

        $this->info('Application was installed successfully!');
        return 0;
    }

    public function setLanguage(): void
    {
        $language = $this->choice('Choose default language', config('app.languages'), 0);

        Settings::scope('core')->set('language', $language);

        $this->info(strtoupper($language) . ' language successfully set');
    }

    protected function registerInstance(string $adminEmail): bool
    {
        return $this->call(RegisterInstance::class, [
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
        EnvUpdater::set('APP_URL', $appUrl);

        $frontendUrl = $this->ask('Trusted frontend domain (e.g. cattr.acme.corp). In most cases, '
            . 'this domain will be the same as the backend (API) one.');
        $frontendUrl = preg_replace('/^https?:\/\//', '', $frontendUrl);
        $frontendUrl = preg_replace('/\/$/', '', $frontendUrl);
        EnvUpdater::set(
            'FRONTEND_APP_URL',
            '"' . $frontendUrl . '"'
        );
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
        $this->info('Running up migrations');
        $this->call(MigrateCommand::class);

        $this->info('Running required seeders');
        $this->call(SeedCommand::class, ['--class' => 'InitialSeeder']);

        EnvUpdater::set('APP_DEBUG', 'false');

        $this->call(KeyGenerateCommand::class, ['-n' => true]);
    }

    protected function createDatabase(): void
    {
        $connectionName = config('database.default');
        $databaseName = config("database.connections.$connectionName.database");

        config(["database.connections.$connectionName.database" => null]);
        DB::purge();

        DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

        config(["database.connections.$connectionName.database" => $databaseName]);
        DB::purge();

        $this->info("Created database $databaseName.");
    }

    protected function settingUpDatabase(): int
    {
        EnvUpdater::bulkSet([
            'DB_HOST' => $this->ask('database host', 'localhost'),
            'DB_PORT' => $this->ask('database port', 3306),
            'DB_USERNAME' => $this->ask('database username', 'root'),
            'DB_PASSWORD' => $this->secret('database password'),
            'DB_DATABASE' => $this->ask('database name', 'app_cattr'),
        ]);

        try {
            DB::connection()->getPdo();

            if (Schema::hasTable('migrations')) {
                throw new RuntimeException('Looks like the application was already installed. '
                    . 'Please, make sure that database was flushed and then try again.');
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
}
