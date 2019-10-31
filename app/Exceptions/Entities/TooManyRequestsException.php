<?php

namespace App\Exceptions\Entities;

use App\Exceptions\Interfaces\ReasonableException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class TooManyRequestsException extends HttpException implements ReasonableException
{
    /**
     * @var mixed
     */
    protected $reason;

    public function __construct(string $message = null, $reason = null, Throwable $previous = null)
    {
        $this->reason = $reason;
        parent::__construct(429, $message, $previous);

    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }
}
