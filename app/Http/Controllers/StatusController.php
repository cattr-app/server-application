<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

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
     * @apiSuccess {String}   cat       A cat for you
     * @apiSuccess {Array}    modules   Information about installed modules
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "cattr": true,
     *    "cat": "(=ㅇ༝ㅇ=)"
     *  }
     */
    /**
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(): JsonResponse
    {
        return responder()->success([
            'cattr' => true,
            'cat' => CatHelper::getCat(),
            'version' => config('app.version'),
        ])->respond();
    }
}
