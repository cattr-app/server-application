<?php

namespace Modules\Reports\Exports\Types;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class Xlsx extends AbstractType implements FromCollection, WithEvents, WithHeadings, WithDrawings, WithCustomStartCell
{
    const LOGO_HEIGHT = 50;
    const COLUMN_HEIGHT = 14; // Value which is close to real one but adding one or few row(-s) of space
    const START_CELL = self::LOGO_HEIGHT / self::COLUMN_HEIGHT;

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $usedCells = 'A1:Z' . ceil(($this->collection->count() + self::START_CELL));
                $headersCell = 'A'. ceil(self::START_CELL) .':A' . ceil(($this->collection->count() + self::START_CELL));
                $totalCells = 'B'. ceil(self::START_CELL) .':B' . ceil(($this->collection->count() + self::START_CELL));
                $totalDecimalCells = 'C'. ceil(self::START_CELL) .':C' . ceil(($this->collection->count() + self::START_CELL));

                $fromCeil = 'B' . (ceil(self::START_CELL / 2));
                $linkCeil = 'C' . (ceil(self::START_CELL / 2));

                $fromText = "Exported from: "; // TODO Should be translated !!

                $frontUrl = Request::server('HTTP_ORIGIN');
                $link = new Hyperlink();
                $link->setUrl($frontUrl);

                $event->sheet->getDelegate()->getStyle($usedCells)->applyFromArray(
                    [
                        'font' => [
                            'name' => 'Arial',
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_NONE,
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                $event->sheet->getDelegate()->getStyle($headersCell)->applyFromArray(
                    [
                        'font' => [
                            'name' => 'Arial',
                            'bold' => true
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::HORIZONTAL_LEFT,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                            ],
                        ],
                    ]);

                // Set colors for main columns
                $event->sheet->getDelegate()->getStyle($headersCell)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('b7ffaf');

                $event->sheet->getDelegate()->getStyle($totalCells)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('c7e2a3');

                $event->sheet->getDelegate()->getStyle($totalDecimalCells)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('c7e2a3');

                // Set borders for main columns
                $event->sheet->getDelegate()->getStyle($headersCell)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                $event->sheet->getDelegate()->getStyle($totalCells)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                $event->sheet->getDelegate()->getStyle($totalDecimalCells)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                // Set link for exported from
                $event->sheet->getDelegate()->getCell($linkCeil)->setValue($frontUrl)->setHyperlink($link);
                $event->sheet->getDelegate()->getCell($fromCeil)->setValue($fromText);

                // Use magick of autoSizing document
                $event->getSheet()->autoSize();
            }
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function headings(): array
    {
        $firstRow = $this->collection->first();

        if ($firstRow instanceof Arrayable || \is_object($firstRow)) {
            return array_keys(Sheet::mapArraybleRow($firstRow));
        }

        return $this->collection->collapse()->keys()->all();
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

    /**
     * @return string
     */
    public function startCell(): string
    {
        return 'A' . ceil(self::START_CELL);
    }

    /**
     * @return Collection
     */
    public function collection()
    {
        return $this->collection;
    }
}
