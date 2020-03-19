<?php


namespace Modules\Reports\Http\Controllers;


use Modules\Reports\Exports\DashboardExport;

class DashboardReportsController extends AbstractReportsController
{

    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'time-intervals.list',
        ];
    }

    /**
     * Get data export class
     */
    protected function exportClass(): string
    {
        return DashboardExport::class;
    }
}
