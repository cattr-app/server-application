<?php

namespace Modules\Reports\Exports;

use Illuminate\Support\Collection;

interface Exportable
{
    /**
     * @return string
     */
    public function getExporterName(): string ;

    /**
     * @return Collection
     */
    public function collection(): Collection ;
}
