<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;

abstract class BaseWebserviceController extends Controller
{
    protected function guard()
    {
        return Auth::guard('api');
    }
}
