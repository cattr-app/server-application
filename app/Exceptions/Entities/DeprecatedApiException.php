<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class DeprecatedApiException extends HttpException
{
    public function __construct()
    {
        $lastCalledMethod = $this->getTrace()[0];
        $deprecatedMethod = "{$lastCalledMethod['class']}@{$lastCalledMethod['function']}";

        \Log::warning("Deprecated method {$deprecatedMethod} called, update Cattr client", [
            'user_id' => auth()->user()->id ?? null
        ]);

        $this->errorCode = 'deprecation.api';
        $this->status = 400;

        parent::__construct("Deprecated method {$deprecatedMethod} called, update Cattr client");
    }
}
