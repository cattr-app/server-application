<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class IntervalAlreadyDeletedException  extends HttpException
{
    protected $errorCode = 'interval_already_deleted';
    protected $status = 409;
}
