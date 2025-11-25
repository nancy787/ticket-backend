<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromArray, WithHeadings
{
    protected $exportData;

    public function __construct(array $exportData)
    {
        $this->exportData = $exportData;
    }

    public function array(): array
    {
        return $this->exportData;
    }

    public function headings(): array
    {
        return [
            'App Users',
            'Male',
            'Female',
            'Others',
            'Not Mentioned',
            'Total',
            'Wishlist Subs'
        ];
    }
}
