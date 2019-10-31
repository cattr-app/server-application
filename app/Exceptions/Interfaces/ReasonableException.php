<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface ReasonableException
 * @package App\Exceptions\Interfaces
 */
interface ReasonableException extends Throwable
{
    /**
     * @return mixed
     */
    public function getReason();
}
