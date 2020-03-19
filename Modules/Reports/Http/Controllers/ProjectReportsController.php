<?php

namespace Modules\Reports\Http\Controllers;

use Modules\Reports\Exports\ProjectExport;

/**
 * Class ProjectReportsController
 */
class ProjectReportsController extends AbstractReportsController
{

    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'project-report.projects',
        ];
    }

    /**
     * Get data export class
     */
    protected function exportClass(): string
    {
        return ProjectExport::class;
    }
}
