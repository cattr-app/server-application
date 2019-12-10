<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    /**
     * @return JsonResponse
     * @api {any} /api/status Status
     * @apiDescription Check API status
     *
     * @apiVersion 0.1.0
     * @apiName Status
     * @apiGroup Status
     *
     *
     * @apiSuccess {Boolean}   amazingtime
     * @apiSuccess {String}   cat
     *
     * @apiSuccessExample {json} Answer Example
     *  {
     *      "amazingtime": true,
     *      "cat": "(=ㅇ༝ㅇ=)"
     *  }
     *
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'amazingtime' => true,
            'cat' => app(CatHelper::class)->getCat(),
        ]);
    }
}
