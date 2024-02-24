<?php

namespace App\Exports;


use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;
use App\Models\BatchStream;
use App\Models\Location;
use App\Models\Subject;
use App\Models\Faculty;

class ScheduleExport implements FromView, WithStyles
{
    protected $schedules;
    protected $location;
    protected $stream;

    public function __construct($schedules, $location, $stream)
    {
        $this->schedules = $schedules;
        $this->location = $location;
        $this->stream = $stream;
    }

//   public function view(): View
// {
//     // Transform schedules to add a combined_code column
//     $transformedSchedules = $this->transformSchedules();

//     return view('exports.schedule', [
//         'schedules' => $transformedSchedules,
//         'location' => $this->location,
//         'stream' => $this->stream,
//     ]);
// }
 public function view(): View
{
    // Get location name based on locationId
    $location = Location::find($this->location);

    if (!$location) {
        // Handle the case where the location is not found
        $locationName = 'Unknown Location';
    } else {
        $locationName = $location->name;
    }

    // Transform schedules to add a combined_code column
    $transformedSchedules = $this->transformSchedules();
    // echo"transformedSchedules-->";print_r($transformedSchedules->toArray());exit;

    // Define facultyColors array
    $facultyColors = [
        '#A1D490', '#97C2FC', '#FFC09F', '#F2A1A8'
        // Add more colors as needed
    ];

    return view('exports.schedule', [
        'schedules' => $transformedSchedules,
        'location' => $locationName, // Use location name instead of ID in the header
        'stream' => $this->stream,
        'subjectColors' => $this->getSubjectColors(), // Pass subjectColors to the view
    ]);
}

// ...

protected function transformSchedules()
{
    return $this->schedules->map(function ($schedule) {
        // Get batch_stream_id from batches_batch_streams table
        $batchStreamEntry = DB::table('batches_batch_streams')
            ->where('batch_id', $schedule->batch_id)
            ->first();

        // If batchStreamEntry is found, get batch_stream from batch_streams table
        $batchStream = BatchStream::find($batchStreamEntry->batch_stream_id);

        // If batchStream is found, get stream_code
        $streamCode = $batchStream ? strtoupper(substr($batchStream->stream_code, 0, 1)) : '';
        
        // Get first letter of the subject code
        $subject = Subject::find($schedule->subject_id);
        $subjectCode = $subject ? strtoupper(substr($subject->subject_code, 0, 1)) : '';
        
        // Get faculty code
        $faculty = Faculty::find($schedule->faculty_id);
        $facultyCode = $faculty ? $faculty->faculty_code : '';

        // Create a new column 'combined_code' with the desired combination
        $schedule['combined_code'] = $subjectCode . $streamCode . $facultyCode;

        // Add a new column 'original_slot_time' to store the original slot time
        $schedule['original_slot_time'] = $schedule->slotTime ? $schedule->slotTime->slot_time : $schedule->slot_time;

        return $schedule;
    });
}

// ...


    // Export styles
    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:A')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
    protected function getSubjectColors()
{
    return [
        'E' => '#FFD3B0',
        'S' => '#F2D7D9',
        'H' => '#B1D7B4',
        'M' => '#D6836F',
        'C' => '#E7B96E',
        'P' => '#85BBCC',
        'B' => '#0caba4',
    ];
}
}