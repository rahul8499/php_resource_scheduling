<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class ReportExport implements FromCollection, WithHeadings
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function collection()
{
    $exportData = new Collection();

    // Define the order of dynamic headings
    $dynamicHeadings = $this->getDynamicHeadings();

    foreach ($this->reportData as $locationData) {
        foreach ($locationData['data']['faculty_data'] as $facultyId => $facultyInfo) {
            $rowData = [
                'First Name' => $facultyInfo['faculty']['first_name'],
                'Last Name' => $facultyInfo['faculty']['last_name'],
                'Faculty Code' => $facultyInfo['faculty']['faculty_code'],
                'Phone' => $facultyInfo['faculty']['phone'],
                'Gender' => $facultyInfo['faculty']['gender'],
            ];

            // Initialize dynamic headings with zero count
            foreach ($dynamicHeadings as $dynamicHeading) {
                $rowData[$dynamicHeading] = 0;
            }

            foreach ($facultyInfo['weeks'] as $weekNumber => $weekInfo) {
                // Call the getWeekDates function to get start and end dates
                $weekDates = $this->getWeekDates($weekInfo['year'], $weekNumber);

                // Add the slot count for the week directly in the row data
                $rowData[$weekDates['start_date'] . ' - ' . $weekDates['end_date']] = count($weekInfo['slots']);
            }

            // Add the row data to the export data
            $exportData->push($rowData);
        }
    }

    return $exportData;
}



    public function headings(): array
{
    // Static headings
    $staticHeadings = [
        'First Name',
        'Last Name',
        'Faculty Code',
        'Phone',
        'Gender',
    ];

    // Collect dynamic headings only once
    $dynamicHeadings = $this->getDynamicHeadings();

    // Combine static and dynamic headings
    return array_merge($staticHeadings, $dynamicHeadings);
}

protected function getDynamicHeadings(): array
{
    $dynamicHeadings = [];

    foreach ($this->reportData as $locationData) {
        foreach ($locationData['data']['faculty_data'] as $facultyInfo) {
            foreach ($facultyInfo['weeks'] as $weekInfo) {
                $weekDates = $this->getWeekDates($weekInfo['year'], $weekInfo['week_number']);
                $dynamicHeading = $weekDates['start_date'] . ' - ' . $weekDates['end_date'];

                // Check if the heading already exists to avoid duplicates and maintain order
                if (!in_array($dynamicHeading, $dynamicHeadings)) {
                    $dynamicHeadings[] = $dynamicHeading;
                }
            }
        }
    }
    // Sort dynamic headings based on start date
    usort($dynamicHeadings, function($a, $b) {
        $startDateA = strtotime(explode(' - ', $a)[0]);
        $startDateB = strtotime(explode(' - ', $b)[0]);
        return $startDateA - $startDateB;
    });

    return $dynamicHeadings;
}
    
    function getWeekDates($year, $weekNumber) {
        $startDate = new \DateTime();
        $startDate->setISODate($year, $weekNumber, 1); // Set the date to the first day (Monday) of the given ISO week

        $endDate = clone $startDate;
        $endDate->modify('+6 days'); // Move to the last day (Sunday) of the week

        return [
            'start_date' => $startDate->format('d-m-Y'),
            'end_date' => $endDate->format('d-m-Y'),
        ];
    }

}
