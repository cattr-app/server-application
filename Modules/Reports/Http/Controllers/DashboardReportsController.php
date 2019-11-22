<?php


namespace Modules\Reports\Http\Controllers;


use Modules\Reports\Exports\DashboardExport;

class DashboardReportsController extends AbstractReportsController
{

    /**
     * Get data export class
     *
     * @return string
     */
    protected function exportClass(): string
    {
        return DashboardExport::class;
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'time-intervals.list',
        ];
    }
}
