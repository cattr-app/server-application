<?php


namespace App\Http\Controllers\Api\v1\Statistic;


use App\Http\Controllers\Controller;

abstract class ReportController extends Controller
{
    /**
     * @param string $eventName
     * @return String
     */
    protected function getEventUniqueName(string $eventName): String
    {
        return "{$eventName}.{$this->getEventUniqueNamePart()}";
    }

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    abstract public function getEventUniqueNamePart(): string;
}
