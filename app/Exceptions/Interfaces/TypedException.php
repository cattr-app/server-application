<?php

namespace App\Exceptions\Interfaces;

use Throwable;

/**
 * Interface TypedException
 */
interface TypedException extends Throwable
{
    /**
     * @return string
     */
    public function getType(): string;
}
