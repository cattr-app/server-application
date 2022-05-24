<?php

namespace App\Http\Controllers;

use App;
use App\Console\Commands\MakeAdmin;
use App\Console\Commands\ResetCommand;
use App\Exceptions\Entities\AppAlreadyInstalledException;
use App\Helpers\EnvUpdater;
use App\Http\Requests\Installation\CheckDatabaseInfoRequest;
use App\Http\Requests\Installation\SaveSetupRequest;
use Artisan;
use Exception;
use Illuminate\Foundation\Console\ConfigCacheCommand;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Settings;
use Throwable;

class InstallationController extends Controller
{
    public function checkDatabaseInfo(CheckDatabaseInfoRequest $request): JsonResponse
    {
        if(Settings::scope('core')->get('installed', false)) {
            throw new AppAlreadyInstalledException;
        }

        config([
            'database.connections.mysql.password' => $request->input('db_password'),
            'database.connections.mysql.database' => $request->input('database'),
            'database.connections.mysql.username' => $request->input('db_user'),
            'database.connections.mysql.host' => $request->input('db_host'),
        ]);

        try {
            DB::reconnect('mysql');
            DB::connection('mysql')->getPDO();

            throw_unless((bool)DB::connection()->getDatabaseName());

            return responder()->success()->respond(204);
        } catch (Throwable) {
            return responder()->error()->respond();
        }
    }

    public function save(SaveSetupRequest $request): JsonResponse
    {
        if(Settings::scope('core')->get('installed', false)) {
            throw new AppAlreadyInstalledException;
        }

        $envFilepath = App::environmentFilePath();

        if (!file_exists($envFilepath)) {
            copy(base_path('.env.example'), $envFilepath);
        }

        if (!env('IMAGE_VERSION')) {
            EnvUpdater::bulkSet([
                'DB_HOST' => $request->input('db_host'),
                'DB_USERNAME' => $request->input('db_user'),
                'DB_PASSWORD' => $request->input('db_password'),
                'DB_DATABASE' => $request->input('database'),
                'DB_PORT' => 3306,
            ]);
        }

        EnvUpdater::bulkSet([
            'RECAPTCHA_ENABLED' => $request->input('captcha_enabled'),
            'RECAPTCHA_SITE_KEY' => (string)$request->input('secret_key'),
            'RECAPTCHA_SECRET_KEY' => (string)$request->input('site_key'),
            'RECAPTCHA_GOOGLE_URL' => 'https://www.google.com/recaptcha/api/siteverify',

            'MAIL_ADDRESS' => $request->input('mail_address'),
            'MAIL_PASS' => $request->input('mail_pass'),
            'MAIL_SERVER' => $request->input('mail_host'),
            'MAIL_PORT' => (int)$request->input('mail_port'),
            'MAIL_SECURITY' => $request->input('encryption'),

            'FRONTEND_APP_URL' => $request->input('origin'),
            'MAIL_FROM_ADDRESS' => 'no-reply@' . explode('//', $request->input('origin'))[1],

            'APP_DEBUG' => 'false',
        ]);

        Artisan::call(ConfigCacheCommand::class);

        $connectionName = config('database.default');
        $databaseName = config("database.connections.$connectionName.database");

        config(["database.connections.$connectionName.database" => null]);
        DB::purge();

        DB::statement("CREATE DATABASE IF NOT EXISTS $databaseName");

        config(["database.connections.$connectionName.database" => $databaseName]);
        DB::purge();

        Artisan::call('migrate', ['--force' => true]);

        Artisan::call(ResetCommand::class, ['--force' => true]);

        Settings::scope('core')->set('language', $request->input('language'));
        Settings::scope('core')->set('timezone', $request->input('timezone'));

        Artisan::call(MakeAdmin::class, [
            'password' => $request->input('password'),
            'name' => 'admin',
            'email' => $request->input('email'),
        ]);

        return responder()->success()->respond(204);
    }
}
