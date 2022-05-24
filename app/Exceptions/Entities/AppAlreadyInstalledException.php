<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class AppAlreadyInstalledException extends HttpException
{
    protected $status = 400;

    protected $errorCode = 'app.installation';

    protected $message = 'App has been already installed';
}
