<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AbstractType implements ExportType
{
    use Exportable {
        download as exportDownload;
        store as storeFile;
    }

    protected string $exporterName;
    protected string $writerType;
    protected string $fileNameType;
    protected Collection $collection;

    public function setExportableName(string $name): void
    {
        $this->exporterName = $name;
    }

    public function download(Collection $collection, string $fileName = null, string $writerType = null, array $headers = null)
    {
        $this->collection = $collection;
        return $this->exportDownload($fileName . '.' . $this->fileNameType, $this->writerType, $headers);
    }

    public function store(Collection $collection, string $filePath = null, string $disk = null)
    {
        $this->collection = $collection;
        return $this->storeFile($filePath, $disk, $this->writerType);
    }

    public function getExporterName(): string
    {
        return $this->exporterName;
    }

    public function getFileNameType(): string
    {
        return $this->fileNameType;
    }

    public function setFileNameType(string $fileNameType): void
    {
        $this->fileNameType = $fileNameType;
    }

    public function getWriterType(): string
    {
        return $this->writerType;
    }

    public function setWriterType(string $writerType): void
    {
        $this->writerType = $writerType;
    }
}
