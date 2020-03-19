<?php

namespace Modules\Reports\Exports;

use Illuminate\Support\Collection;

interface Exportable
{
    public function getExporterName(): string;

    public function collection(): Collection;
}
