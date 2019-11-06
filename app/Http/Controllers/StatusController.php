<?php

namespace App\Http\Controllers;

use App\Helpers\CatHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class StatusController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $data = [
            'amazingtime' => true,
            'cat' => app(CatHelper::class)->getCat(),
        ];

        return response()->json($data);
    }
}
