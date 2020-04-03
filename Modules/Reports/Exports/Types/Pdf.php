<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Request;

class Pdf extends AbstractType implements FromView, WithEvents, WithDrawings
{
    public const LOGO_HEIGHT = 50;

    public function view(): View
    {
        return view($this->getViewName(), [
            'collection' => $this->collection
        ]);
    }

    public function getViewName(): string
    {
        return 'reports::' . $this->exporterName;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => static function (AfterSheet $event) {
                $fromCeil = 'B1';
                $linkCeil = 'C1';

                $fromText = 'Exported from: '; // TODO Should be translated !!

                $frontUrl = Request::server('HTTP_ORIGIN');
                $link = new Hyperlink();
                $link->setUrl($frontUrl);

                // Disable table borders
                $event->sheet->getDelegate()->setShowGridlines(true);

                // Set hyperlink to exported from
                $event->sheet->getDelegate()->getCell($linkCeil)->setValue($frontUrl)->setHyperlink($link);
                $event->sheet->getDelegate()->getCell($fromCeil)->setValue($fromText);

                // Columns width formatting
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(25);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(25);

                $event->sheet->getDelegate()->getStyle('A1:' . $event->sheet->getDelegate()->getHighestColumn() . '1')
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_NONE,
                            ]
                        ]
                    ]);

                $event->sheet->getDelegate()->getStyle('A2:' . $event->sheet->getDelegate()->getHighestColumn() . '2')
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_NONE,
                            ]
                        ]
                    ]);

                $sortedCells = $event->sheet->getDelegate()->getCellCollection()->getSortedCoordinates();
                $lastCell = $sortedCells[count($sortedCells) - 1];
                $event->sheet->getDelegate()->getStyle('A3:' . $lastCell)
                    ->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => [
                                    'rgb' => '000000'
                                ]
                            ],
                        ],
                    ]);
            }
        ];
    }

    /**
     * @return BaseDrawing|BaseDrawing[]
     * @throws Exception
     */
    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo')
            ->setOffsetX(self::LOGO_HEIGHT / 6)
            ->setDescription('Logo')
            ->setPath(storage_path('app/public/logo.png'))
            ->setHeight(self::LOGO_HEIGHT)
            ->setResizeProportional(true);

        return $drawing;
    }
}
