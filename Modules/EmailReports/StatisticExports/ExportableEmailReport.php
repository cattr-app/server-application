<?php

namespace Modules\EmailReports\StatisticExports;

use Illuminate\Support\Collection;

interface ExportableEmailReport
{
    /**
     * @param array $queryData
     * @return Collection
     */
    public function exportCollection(array $queryData): Collection;
}
