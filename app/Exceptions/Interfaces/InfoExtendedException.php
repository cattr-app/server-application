<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface ReasonableException
 */
interface InfoExtendedException extends Throwable
{
    /**
     * @return mixed
     */
    public function getInfo();
}
