<?php namespace App\Http\Controllers\Api\v1;

// use App\Libraries\WebService;
// use App\Libraries\CustomErrorHandler;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseWebserviceController;

use Illuminate\Support\Facades\Input;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;

use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

use Lang;
use Config;
use Response;
use File;
use DB;
use Carbon;
use Validator;

class WebserviceController extends BaseWebserviceController
{
    public function create()
    {
        return response()->json([
            'some' => 'data',
        ]);
    }

    public function show()
    {
        return response()->json([
            'some' => 'data',
        ]);
    }
}
