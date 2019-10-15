<?php

namespace App\Exceptions\Entities;

use \Illuminate\Auth\Access\AuthorizationException as AuthorizationExceptionCore;
use Throwable;

/**
 * Class AuthorizationException
 * @package App\Exceptions\Entities
 */
class AuthorizationException extends AuthorizationExceptionCore
{
    /**
     * @var string
     */
    protected $reason = '';

    /**
     * AuthorizationException constructor.
     * @param string $message
     * @param string $reason
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', $reason = '', $code = 401, Throwable $previous = null)
    {
        $this->reason = $reason;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
