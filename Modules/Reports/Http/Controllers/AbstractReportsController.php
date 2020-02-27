<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
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
     *
     * @param  Request  $request
     * @param  Excel    $exporter
     */
    public function __construct(Request $request, Excel $exporter)
    {
        $this->request = $request;
        $this->excel = $exporter;
        $this->exporter = app($this->exportClass());
        parent::__construct();
    }

    /**
     * @return BinaryFileResponse
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

        return response()->json(['error' => 'There is no applicable accept header']);
    }

    /**
     * Get data export class
     *
     * @return string
     */
    abstract protected function exportClass(): string;
}
