<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WishlistExport implements FromArray, WithHeadings
{
    protected $exportData;
    protected $allCategories;

    public function __construct(array $exportData, $allCategories)
    {
        $this->exportData = $exportData;
        $this->allCategories = $allCategories;
    }

    public function array(): array
    {
        return $this->exportData;
    }

    public function headings(): array
    {
        return array_merge(['Event'], $this->allCategories->toArray(), ['Total Categories']);
    }
}
