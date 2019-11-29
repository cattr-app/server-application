<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface ReasonableException
 * @package App\Exceptions\Interfaces
 */
interface InfoExtendedException extends Throwable
{
    /**
     * @return mixed
     */
    public function getInfo();
}
