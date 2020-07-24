<?php

namespace App\Http\Controllers\Api\Statistic;

use App\Http\Controllers\Controller;

abstract class ReportController extends Controller
{
    protected function getEventUniqueName(string $eventName): String
    {
        return "{$eventName}.{$this->getEventUniqueNamePart()}";
    }

    /**
     * Returns unique part of event name for current item
     */
    abstract public function getEventUniqueNamePart(): string;
}
