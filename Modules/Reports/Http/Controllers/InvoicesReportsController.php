<?php

namespace Modules\Reports\Http\Controllers;

use Modules\Reports\Exports\InvoicesExport;

class InvoicesReportsController extends AbstractReportsController
{

    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'invoices.list',
        ];
    }

    /**
     * Get data export class
     */
    protected function exportClass(): string
    {
        return InvoicesExport::class;
    }
}
