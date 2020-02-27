<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Request;

/**
 * Class Pdf
 * @package Modules\Reports\Exports\Types
 */
class Pdf extends AbstractType implements FromView, WithEvents, WithDrawings
{
    const LOGO_HEIGHT = 180;

    /**
     * @return View
     */
    public function view(): View
    {
        return view($this->getViewName(), [
            'collection' => $this->collection
        ]);
    }

    /**
     * @return string
     */
    public function getViewName(): string
    {
        return 'reports::' . $this->exporterName;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $fromCeil = 'B2';
                $linkCeil = 'C2';

                $fromText = "Exported from: "; // TODO Should be translated !!

                $frontUrl = Request::server('HTTP_ORIGIN');
                $link = new Hyperlink();
                $link->setUrl($frontUrl);

                // Disable table borders
                $event->sheet->getDelegate()->setShowGridlines(false);

                // Set hyperlink to exported from
                $event->sheet->getDelegate()->getCell($linkCeil)->setValue($frontUrl)->setHyperlink($link);
                $event->sheet->getDelegate()->getCell($fromCeil)->setValue($fromText);

                // Columns width formatting
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B')->setAutoSize(true);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('C')->setCollapsed(true);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(20);

                $event->sheet->getDelegate()->getStyle('A1:' . $event->sheet->getDelegate()->getHighestDataColumn() . $this->collection->count())
                    ->getAlignment()->setWrapText(true);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(25);

                // Set headers alignment 'center'
                $event->sheet->getDelegate()->getStyle('A3:' . $event->sheet->getDelegate()->getHighestDataColumn() . $this->collection->count())
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle('A3:' . $event->sheet->getDelegate()->getHighestDataColumn() . $this->collection->count())
                    ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            }
        ];
    }

    /**
     * @return BaseDrawing|BaseDrawing[]
     * @throws \PhpOffice\PhpSpreadsheet\Exception
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
