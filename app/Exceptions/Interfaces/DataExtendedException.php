<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface ReasonableException
 * @package App\Exceptions\Interfaces
 */
interface DataExtendedException extends Throwable
{
    /**
     * @return mixed
     */
    public function getData();
}
