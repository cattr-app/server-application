<?php
namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;

/**
 * Class BaseWebserviceController
 *
 * @package App\Http\Controllers\Api
 */
abstract class BaseWebserviceController extends Controller
{
    /**
     * |\Illuminate\Contracts\Auth\StatefulGuard
     * @return Guard
     */
    protected function guard(): Guard
    {
        return Auth::guard('api');
    }
}
