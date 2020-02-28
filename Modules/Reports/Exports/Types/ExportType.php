<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Support\Collection;

interface ExportType
{
    /**
     * @param Collection $collection
     * @param string $fileName
     * @param string|null $writerType
     * @param array $headers
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Collection $collection, string $fileName = null, string $writerType = null, array $headers = null);
}
