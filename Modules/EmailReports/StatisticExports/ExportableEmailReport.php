<?php

namespace Modules\EmailReports\StatisticExports;

use Illuminate\Support\Collection;

interface ExportableEmailReport
{
    public function exportCollection(array $queryData): Collection;
}
