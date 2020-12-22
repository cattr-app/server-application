<?php

namespace App\Http\Controllers;

use App\Helpers\ModuleHelper;
use App\Http\Requests\Installation\CheckDatabaseInfoRequest;
use Illuminate\Routing\Controller;
use App\Models\Property;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Exception;

class InstallationController extends Controller
{
    public function getStatusOfLoading(): JsonResponse
    {
        return new JsonResponse([
            'status' => file_exists(app()->environmentFilePath()) && User::where(['is_admin' => 1])->count(),
        ]);
    }

    public function checkDatabaseInfo(CheckDatabaseInfoRequest $request): JsonResponse
    {
        config([
            'database.connections.mysql.password' => $request->input('password'),
            'database.connections.mysql.database' => $request->input('database'),
            'database.connections.mysql.username' => $request->input('user'),
            'database.connections.mysql.host' => $request->input('host'),
        ]);

        try {
            DB::reconnect('mysql');
            DB::connection('mysql')->getPDO();

            return new JsonResponse(['status' => (bool) DB::connection()->getDatabaseName()]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => false]);
        }
    }

    public function registrationInCollector(Request $request, Client $client): JsonResponse
    {
        $email = $request->input('email') ?? null;
        if (!$email) {
            return new JsonResponse([
                'success' => false,
            ], 400);
        }

        if (Property::where([
            'entity_type' => Property::APP_CODE,
            'entity_id' => 0,
            'name' => 'INSTANCE_ID',
        ])->count()) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Application already registered'
            ]);
        }

        try {
            $appVersion = config('app.version');

            $response = $client->post(config('app.stats_collector_url') . '/instance', [
                'json' => [
                    'ownerEmail' => $email,
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
                Property::updateOrCreate([
                    'entity_type' => Property::APP_CODE,
                    'entity_id' => 0,
                    'name' => 'INSTANCE_ID'
                ], [
                    'value' => $responseBody['instanceId']
                ]);
            }

            return new JsonResponse([
                'success' => true,
            ]);
        } catch (Exception $e) {
            if ($e->getResponse()) {
                $envFilePath = app()->environmentFilePath();
                if ($str = file_exists($envFilePath)) {
                    unlink($envFilePath);
                }

                $error = json_decode(
                    $e->getResponse()->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR
                );

                return new JsonResponse([
                    'success' => false,
                    'message' => $error['message']
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'message' => 'Сould not get a response from the server to check the relevance of your version.',
            ], 400);
        }
    }

    public function changeEnvFile(Request $request): JsonResponse
    {
        $envValues = $request->toArray();
        $envFilepath = app()->environmentFilePath();

        if ($file = file_exists($envFilepath)) {
            $str = file_get_contents($envFilepath);
        } else {
            copy(base_path('.env.example'), $envFilepath);
            $str = file_get_contents($envFilepath);
        }

        foreach ($envValues as $envKey => $envValue) {
            $str .= "\n"; // In case the searched variable is in the last line without \n
            $keyPosition = strpos($str, "{$envKey}=");
            $endOfLinePosition = strpos($str, "\n", $keyPosition);
            $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

            // If key does not exist, add it
            if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                $str .= "{$envKey}={$envValue}\n";
            } else {
                $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
            }
        }

        $str = substr($str, 0, -1);
        if (!file_put_contents($envFilepath, $str)) {
            return new JsonResponse([
                'success' => false,
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
            'is_docker' => env('IMAGE') ? true : false,
        ]);
    }

    public function createAdmin(Request $request): JsonResponse
    {
        $accountParams = [
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'timezone' => $request->input('timezone'),
            'user_language' => $request->input('language')
        ];

        if (!$accountParams['email']
            || !$accountParams['password']
            || !$accountParams['timezone']
            || !$accountParams['user_language']
        ) {
            return new JsonResponse([
                'success' => false,
            ], 400);
        }

        $admin = User::firstOrNew(['is_admin' => 1]);

        $admin->fill(
            array_merge([
                'full_name' => 'Admin',
                'active' => true,
                'is_admin' => true,
                'role_id' => 2,
            ], $accountParams)
        );

        abort_if(!$admin->save(), 400);

        return new JsonResponse();
    }

    public function setConfig(): JsonResponse
    {
        $a = $_SERVER;

        return new JsonResponse();
    }
}
