<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class InvalidMainException extends HttpException
{
    protected $status = 422;

    protected $message = 'Base mistranslation detected';
}
