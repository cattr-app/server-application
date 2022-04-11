<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class MethodNotAllowedException extends HttpException
{
    protected $status = 405;

    protected $errorCode = 'http.request.wrong_method';
}
