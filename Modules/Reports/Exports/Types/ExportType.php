<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Support\Collection;

interface ExportType
{
    /**
     * @param Collection $collection
     * @param string $fileName
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Collection $collection, string $fileName = null);
}
