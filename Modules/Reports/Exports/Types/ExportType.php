<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

interface ExportType
{
    public function download(Collection $collection, string $fileName = null, string $writerType = null, array $headers = null);
}
