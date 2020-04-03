<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
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
     * @apiSuccess {Boolean}  cattr  Indicates successful request when `TRUE`
     * @apiSuccess {String}   cat          A cat for you
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "cattr": true,
     *    "cat": "(=ㅇ༝ㅇ=)"
     *  }
     */
    public function index(): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'cattr' => true,
            'cat' => app(CatHelper::class)->getCat(),
        ]);
    }
}
