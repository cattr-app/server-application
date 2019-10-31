<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface TypedException
 * @package App\Exceptions\Interfaces
 */
interface TypedException extends Throwable
{
    /**
     * @return string
     */
    public function getType(): string;
}
