<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    /**
     * Status
     *
     * Check API status
     *
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
