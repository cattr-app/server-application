<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;

class AbstractType implements ExportType
{
    use Exportable {
        download as exportDownload;
        store as storeFile;
    }

    /**
     * @var string
     */
    protected $exporterName;

    /**
     * @var string
     */
    protected $writerType;

    /**
     * @var string
     */
    protected $fileNameType;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @param string $name
     */
    public function setExportableName(string $name)
    {
        $this->exporterName = $name;
    }

    /**
     * @param string $writerType
     */
    public function setWriterType(string $writerType)
    {
        $this->writerType = $writerType;
    }

    /**
     * @param string $fileNameType
     */
    public function setFileNameType(string $fileNameType)
    {
        $this->fileNameType = $fileNameType;
    }

    /**
     * @param Collection $collection
     * @param string|null $fileName
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Collection $collection, string $fileName = null)
    {
        $this->collection = $collection;
        return $this->exportDownload($fileName . '.' . $this->fileNameType, $this->writerType);
    }

    /**
     * @param Collection $collection
     * @param string|null $filePath
     * @param string|null $disk
     *
     * @return bool|\Illuminate\Foundation\Bus\PendingDispatch
     */
    public function store(Collection $collection, string $filePath = null, string $disk = null)
    {
        $this->collection = $collection;
        return $this->storeFile($filePath, $disk, $this->writerType);
    }

    /**
     * @return string
     */
    public function getExporterName(): string
    {
        return $this->exporterName;
    }

    /**
     * @return string
     */
    public function getFileNameType(): string
    {
        return $this->fileNameType;
    }

    /**
     * @return string
     */
    public function getWriterType(): string
    {
        return $this->writerType;
    }
}
