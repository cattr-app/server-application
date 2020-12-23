<?php

namespace App\Http\Controllers;

use App\Console\Commands\MakeAdmin;
use App\Console\Commands\ResetCommand;
use App\Http\Requests\Installation\CheckDatabaseInfoRequest;
use App\Http\Requests\Installation\SaveSetupRequest;
use Artisan;
use EnvEditor\EnvFile;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Settings;
use Tymon\JWTAuth\Console\JWTGenerateSecretCommand;

class InstallationController extends Controller
{
    public function checkDatabaseInfo(CheckDatabaseInfoRequest $request): JsonResponse
    {
        config([
            'database.connections.mysql.password' => $request->input('db_password'),
            'database.connections.mysql.database' => $request->input('database'),
            'database.connections.mysql.username' => $request->input('db_user'),
            'database.connections.mysql.host' => $request->input('db_host'),
        ]);

        try {
            DB::reconnect('mysql');
            DB::connection('mysql')->getPDO();

            return new JsonResponse(['status' => (bool)DB::connection()->getDatabaseName()]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => false]);
        }
    }

    public function save(SaveSetupRequest $request): JsonResponse
    {
        $envFilepath = app()->environmentFilePath();

        if (!file_exists($envFilepath)) {
            copy(base_path('.env.example'), $envFilepath);
        }

        $envFile = EnvFile::loadFrom($envFilepath);

        if (!env('IMAGE_VERSION')) {
            $envFile->setValue('DB_HOST', $request->input('db_host'));
            $envFile->setValue('DB_USERNAME', $request->input('db_user'));
            $envFile->setValue('DB_PASSWORD', $request->input('db_password'));
            $envFile->setValue('DB_DATABASE', $request->input('database'));
            $envFile->setValue('DB_PORT', 3306);
        }

        $envFile->setValue('RECAPTCHA_ENABLED', $request->input('captcha_enabled'));
        $envFile->setValue('RECAPTCHA_SITE_KEY', (string)$request->input('secret_key'));
        $envFile->setValue('RECAPTCHA_SECRET_KEY', (string)$request->input('site_key'));
        $envFile->setValue('RECAPTCHA_GOOGLE_URL', 'https://www.google.com/recaptcha/api/siteverify');

        $envFile->setValue('FRONTEND_APP_URL', $request->input('origin'));
        $envFile->setValue('MAIL_FROM_ADDRESS', 'no-reply@' . explode('//', $request->input('origin'))[1]);

        $envFile->setValue('APP_DEBUG', 'false');

        $envFile->saveTo($envFilepath);

        Artisan::call(JWTGenerateSecretCommand::class, ['--force' => true]);
        Artisan::call(ConfigCacheCommand::class);

        $connectionName = config('database.default');
        $databaseName = config("database.connections.{$connectionName}.database");

        config(["database.connections.{$connectionName}.database" => null]);
        DB::purge();

        DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

        config(["database.connections.{$connectionName}.database" => $databaseName]);
        DB::purge();

        Artisan::call(ResetCommand::class, ['--force' => true]);

        Settings::scope('core')->set('language', $request->input('language'));
        Settings::scope('core')->set('timezone', $request->input('timezone'));

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => 'InitialSeeder']);

        Artisan::call(MakeAdmin::class, [
            'password' => $request->input('password'),
            'name' => 'admin',
            'email' => $request->input('email')
        ]);

        Settings::scope('core')->set('installed', true);

        return new JsonResponse(['status' => true]);
    }
}
