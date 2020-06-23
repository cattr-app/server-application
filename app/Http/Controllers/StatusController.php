<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Filter;

class StatusController extends Controller
{
    /**
     * @api             {get} /status Status
     * @apiDescription  Check API status
     *
     * @apiVersion      1.0.0
     * @apiName         Status
     * @apiGroup        Status
     *
     * @apiSuccess {Boolean}  success   Request status
     * @apiSuccess {Boolean}  cattr     Indicates successful request when `TRUE`
     * @apiSuccess {String}   cat       A cat for you
     * @apiSuccess {Array}    modules   Information about installed modules
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "cattr": true,
     *    "cat": "(=ㅇ༝ㅇ=)"
     *  }
     */
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'cat' => CatHelper::getCat()
        ]);
    }

    public function statusDatabase(Request $request): JsonResponse
    {
        $dbData = [
            'host' => $request->input('host_name'),
            'database' => $request->input('database_name'),
            'username' => $request->input('user_name'),
            'password' => $request->input('password'),
        ];

        if (!$dbData['host'] || !$dbData['database'] || !$dbData['username'] || !$dbData['password']) {
            return new JsonResponse([
                'success' => false,
            ]);
        }

        config([
            'database.connections.mysql.password' => $dbData['password'],
            'database.connections.mysql.database' => $dbData['database'],
            'database.connections.mysql.username' => $dbData['username'],
            'database.connections.mysql.host' => $dbData['host'],
        ]);

//        $validator = Validator::make(
//            $dbData,
//            Filter::process(
//                $this->getEventUniqueName('validation.item.create'),
//                $this->getValidationRules()
//            )
//        );

//        $envFile = app()->environmentFilePath();
//        $str = file_get_contents($envFile);
//
//        foreach ($dbData as $envKey => $envValue) {
//
//            $str .= "\n"; // In case the searched variable is in the last line without \n
//            $keyPosition = strpos($str, "{$envKey}=");
//            $endOfLinePosition = strpos($str, "\n", $keyPosition);
//            $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
//
//            // If key does not exist, add it
//            if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
//                $str .= "{$envKey}={$envValue}\n";
//            } else {
//                $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
//            }
//        }
//
//        $str = substr($str, 0, -1);
//        if (!file_put_contents($envFile, $str)) {
//            return new JsonResponse([
//                'success' => false,
//                'cat' => CatHelper::getCat()
//            ]);
//        }

        Artisan::call('config:clear');

        try {
            DB::connection()->getPDO();
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
            ], 400);
        }

        return new JsonResponse([
            'success' => true,
        ]);
    }

    public function getValidationRules(): array
    {
        return [
            'host_name' => 'required',
            'database_name' => 'required',
            'user_name' => 'required',
            'password' => 'required',
        ];
    }
}
