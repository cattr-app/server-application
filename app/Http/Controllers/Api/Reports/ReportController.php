<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;

abstract class ReportController extends Controller
{
    protected function getEventUniqueName(string $eventName): String
    {
        return "{$eventName}.{$this->getEventUniqueNamePart()}";
    }
}
