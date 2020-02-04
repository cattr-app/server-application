<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
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
     * @return \Maatwebsite\Excel\BinaryFileResponse|BinaryFileResponse
     * @throws Exception
     */
    public function getReport()
    {
        switch ($this->request->headers->get('Accept')) {
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                $type = Excel::XLSX;
                $fsType = 'xlsx';
                break;
            case 'text/csv':
                $type = Excel::CSV;
                $fsType = 'csv';
                break;
            case 'application/pdf':
                $type = Excel::MPDF;
                $fsType = Excel::MPDF;
                break;
            default:
                return response()->json(['error' => 'There is no applicable accept header']);
        }

        return $this->exporter->collection()
            ->downloadExcel(time() . '_project_export.' . $fsType, $type, true);
    }

    /**
     * Get data export class
     *
     * @return string
     */
    abstract protected function exportClass(): string;
}
