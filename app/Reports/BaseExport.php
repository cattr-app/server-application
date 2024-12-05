<?php

namespace App\Reports;

use Carbon\CarbonInterval;

abstract class BaseExport
{
    protected function formatDuration(int $seconds): string
    {
        if ($seconds === 0) {
            return '-';
        }

        return CarbonInterval::seconds($seconds)->cascade()->forHumans(['short' => true]);
    }
}
