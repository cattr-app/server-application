<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class InstallationException extends HttpException
{
    protected $status = 400;

    protected $errorCode = 'app.installation';

    protected $message = 'You need to run installation';
}
