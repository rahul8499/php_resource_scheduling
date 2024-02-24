<?php
namespace App\Exports;

use App\Models\Batch;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Database\Eloquent\Collection;

class BatchExport implements FromView, WithStyles
{
    protected $batches;

    // public function __construct($batches)
    // {
    //     $this->batches = $batches;
    // }
    public function __construct(Collection $batches)
    {
        $this->batches = $batches->load('locations'); // Eager load the locations relationship
    }

    // public function collection()
    // {
    //     return $this->batches->map(function ($batch) {
    //         return [
    //             'batch_code' => $batch->batch_code,
    //             'batch_type' => optional($batch->batchTypes->first())->name,
    //             'batches_batch_slots' => optional($batch->BatchSlots->first())->slot,
    //             'batch_stream' => optional($batch->batchStream->first())->stream_names,
    //             'starting_date' => $batch->starting_date,
    //             'duration' => $batch->duration . ' ' . $batch->duration_type,
    //             'location' => optional($batch->locations->first())->name,
    //         ];
    //     });
    // }

      public function view(): View
    {
        return view('exports.batch', [
            'batches' => $this->batches,
        ]);
    }
      
// public function headings(): array
//     {
//         return [
//             'Batch Code',
//             'Batch Type',
//             'Batch Slots',
//             'Batch Stream',
//             'Starting Date',
//             'Duration',
//             'Location',
//         ];
//     }

     public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
    {
        $sheet->getStyle('A:A')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
