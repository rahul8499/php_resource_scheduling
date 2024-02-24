<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class FacultyExport implements FromView, WithStyles
{
    protected $faculties;

    public function __construct($faculties)
    {
        $this->faculties= $faculties;
    }

    public function view(): View
    {
        return view('exports.faculty', [
            'faculties' => $this->faculties,
        ]);
    }

    public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $sheet->getStyle('A:A')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
