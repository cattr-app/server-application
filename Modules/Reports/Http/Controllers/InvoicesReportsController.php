<?php

namespace Modules\Reports\Http\Controllers;

use Modules\Reports\Exports\InvoicesExport;

class InvoicesReportsController extends AbstractReportsController
{

    /**
     * Get data export class
     *
     * @return string
     */
    protected function exportClass(): string
    {
        return InvoicesExport::class;
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'invoices.list',
        ];
    }
}
