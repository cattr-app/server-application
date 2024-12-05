<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class NotEnoughRightsException extends HttpException
{
    public function __construct(string $message = 'Not enoughs rights')
    {
        $this->status = 403;

        parent::__construct($message);
    }
}
