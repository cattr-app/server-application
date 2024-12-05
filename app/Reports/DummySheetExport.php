<?php
namespace App\Reports;

use Maatwebsite\Excel\Concerns\FromArray;



class DummySheetExport implements FromArray
{
    protected $title;

    public function __construct($title = 'Empty Sheet')
    {
        $this->title = $title;
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return $this->title;
    }
}
