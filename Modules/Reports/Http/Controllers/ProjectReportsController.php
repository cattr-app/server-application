<?php

namespace Modules\Reports\Http\Controllers;

use Modules\Reports\Exports\ProjectExport;

/**
 * Class ProjectReportsController
 *
 * @package Modules\Reports\Http\Controllers
 */
class ProjectReportsController extends AbstractReportsController
{

    /**
     * Get data export class
     *
     * @return string
     */
    protected function exportClass(): string
    {
        return ProjectExport::class;
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'getReport' => 'project-report.projects',
        ];
    }
}
