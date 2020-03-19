<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Modules\Reports\Exports\Types\Csv;
use Modules\Reports\Exports\Types\ExportType;
use Modules\Reports\Exports\Types\Pdf;
use Modules\Reports\Exports\Types\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class AbstractReportsController
 */
abstract class AbstractReportsController extends Controller
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Excel
     */
    protected $excel;

    /**
     * @var mixed
     */
    protected $exporter;

    /**
     * ReportsController constructor.
     */
    public function __construct(Request $request, Excel $exporter)
    {
        $this->request = $request;
        $this->excel = $exporter;
        $this->exporter = app($this->exportClass());
        parent::__construct();
    }

    /**
     * Get data export class
     */
    abstract protected function exportClass(): string;

    /**
     * @return BinaryFileResponse|JsonResponse
     * @throws Exception
     */
    public function getReport()
    {
        $report = false;
        $writerType = '';
        $fileNameType = '';

        switch ($this->request->headers->get('Accept')) {
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $writerType = Excel::XLSX;
                $fileNameType = 'xlsx';
                $report = new Xlsx();

                break;
            case 'text/csv':
                $writerType = Excel::CSV;
                $fileNameType = 'csv';
                $report = new Csv();

                break;
            case 'application/pdf':
                $writerType = Excel::MPDF;
                $fileNameType = 'pdf';
                $report = new Pdf();
                break;
        }

        if ($report && $report instanceof ExportType) {
            $report->setExportableName($this->exporter->getExporterName());
            $report->setFileNameType($fileNameType);
            $report->setWriterType($writerType);
            return $report->download($this->exporter->collection(), time() . '_project_export');
        }

        return new JsonResponse(['error' => 'There is no applicable accept header']);
    }
}
