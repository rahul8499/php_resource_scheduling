<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Location;
use App\Models\Faculty;
use Carbon\Carbon;
use App\Exports\ReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private function buildReportData($schedules)
    {
        $result = [];

        foreach ($schedules as $schedule) {
            $faculty = $schedule->faculty;
            $facultyId = $faculty->id;
            $slotTime = $schedule->slot_time;
            $locationId = $schedule->location_id;
            $locationName = $schedule->location->name;

            if (!isset($result[$locationId])) {
                $result[$locationId] = [
                    'location_id' => $locationId,
                    'location_name' => $locationName,
                    'data' => [
                        'faculty_data' => [],
                    ],
                ];
            }

            if (!isset($result[$locationId]['data']['faculty_data'][$facultyId])) {
                $result[$locationId]['data']['faculty_data'][$facultyId] = [
                    'id' => $facultyId,
                    'faculty' => $faculty,
                    'weeks' => [],
                    'month_hours' => [],
                    'year_hours' => [],
                ];
            }

            $classStartTime = strtotime($slotTime);
            $classEndTime = $classStartTime + (100 * 60);
            $classStartTime = date('H:i', $classStartTime);
            $classEndTime = date('H:i', $classEndTime);
            $Date = date('Y-m-d', strtotime($schedule->date));
            $weekNumber = date('W', strtotime($Date));
            $month = date('F', strtotime($Date));
            $year = date('Y', strtotime($schedule->date));
            $scheduleHours = 100 / 60;

            $slotData = [
                'slot_time' => $slotTime,
                'class_start_time' => $classStartTime,
                'class_end_time' => $classEndTime,
            ];

            if (!isset($result[$locationId]['data']['faculty_data'][$facultyId]['weeks'][$weekNumber])) {
                $result[$locationId]['data']['faculty_data'][$facultyId]['weeks'][$weekNumber] = [
                    'week_number' => $weekNumber,
                    'slots' => [],
                    'week_total_hours' => 0,
                    'year' => $year,
                ];
            }

            if (!isset($result[$locationId]['data']['faculty_data'][$facultyId]['month_hours'][$month])) {
                $result[$locationId]['data']['faculty_data'][$facultyId]['month_hours'][$month] = [
                    'month' => $month,
                    'slots' => [],
                    'month_total_hours' => 0,
                    'year' => $year,
                ];
            }
             if (!isset($result[$locationId]['data']['faculty_data'][$facultyId]['year_hours'][$year])) {
                $result[$locationId]['data']['faculty_data'][$facultyId]['year_hours'][$year] = [
                    'year' => $year,
                    'total_hours' => 0,
                ];
            }

            $result[$locationId]['data']['faculty_data'][$facultyId]['weeks'][$weekNumber]['slots'][] = $slotData;
            $result[$locationId]['data']['faculty_data'][$facultyId]['weeks'][$weekNumber]['week_total_hours'] += $scheduleHours;

            $result[$locationId]['data']['faculty_data'][$facultyId]['month_hours'][$month]['slots'][] = $slotData;
            $result[$locationId]['data']['faculty_data'][$facultyId]['month_hours'][$month]['month_total_hours'] += $scheduleHours;

            $result[$locationId]['data']['faculty_data'][$facultyId]['year_hours'][$year]['total_hours'] += $scheduleHours;
        }

        return $result;
    }

    public function getReport(Request $req)
    {
        $startDate = $req->query('starting_date');
        $endDate = $req->query('ending_date');
        $locationId = $req->query('location_id');
        $sortField = $req->query('sort_by', 'faculty');
        $sortOrder = $req->query('sort_order', 'asc');
        $searchTerm = $req->query('search_term');
        $perPage = $req->query('limit', 10);
        $currentPage = $req->query('page', 1);

        $schedules = Schedule::with(['faculty.subject', 'subject', 'batch.batchSlots', 'slotTime'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($locationId) {
            $schedules->where('location_id', $locationId);
        }

        if ($searchTerm) {
            $schedules->whereHas('faculty', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            })->orWhereHas('location', function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            })->orWhereHas('subject', function ($query) use ($searchTerm) {
                $query->where('subject_name', 'like', '%' . $searchTerm . '%');
            });
        }

        if ($sortField === 'faculty') {
            $schedules->join('faculties', 'schedules.faculty_id', '=', 'faculties.id')
                ->orderBy('faculties.first_name', $sortOrder);
        }

        $schedules = $schedules->get();

        $reportData = $this->buildReportData($schedules);

        $locations = Location::all();

        $responseData = [];
        foreach ($reportData as $locationData) {
            $facultyData = $locationData['data']['faculty_data'];

            $formattedFacultyData = [];
            foreach ($facultyData as $facultyId => $facultyInfo) {
                $formattedWeeks = [];
                foreach ($facultyInfo['weeks'] as $weekInfo) {
                    $formattedWeeks[] = [
                        'week' => $weekInfo['week_number'],
                        'slots' => $weekInfo['slots'],
                        'week_total_hours' => $weekInfo['week_total_hours'],
                        'year' => $weekInfo['year'],
                    ];
                }

                $formattedMonthHours = [];
                foreach ($facultyInfo['month_hours'] as $month => $monthInfo) {
                    $formattedMonthHours[] = [
                        'month' => $monthInfo['month'],
                        'slots' => $monthInfo['slots'],
                        'month_total_hours' => $monthInfo['month_total_hours'],
                        'year' => $monthInfo['year'],
                    ];
                }

                $formattedFacultyData[] = [
                    'id' => $facultyInfo['id'],
                    'faculty' => $facultyInfo['faculty'],
                    'weeks' => $formattedWeeks,
                    'month_hours' => $formattedMonthHours,
                    'year_hours' => $facultyInfo['year_hours'],
                ];
            }
            // Manually paginate $formattedFacultyData
            $start = ($currentPage - 1) * $perPage;
            $formattedFacultyData = array_slice($formattedFacultyData, $start, $perPage);

            $paginatedFacultyData[] = [
                'location_id' => $locationData['location_id'],
                'location_name' => $locationData['location_name'],
                'data' => ['faculty_data' => $formattedFacultyData],
            ];
        }

        $weekTotalHours = $this->calculateWeekTotalHours($reportData);

        // $paginatedData = collect($responseData)
        //     ->forPage($currentPage, $perPage)
        //     ->values();

        return response()->json([
            'location_id' => $paginatedFacultyData,
            'week_total_hours' => $weekTotalHours,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => count($facultyData),
            ],
        ]);
    }

    private function calculateWeekTotalHours($reportData)
    {
        $weekTotalHours = 0;
        foreach ($reportData as $locationData) {
            $facultyData = $locationData['data']['faculty_data'];
            foreach ($facultyData as $facultyInfo) {
                foreach ($facultyInfo['weeks'] as $week) {
                    $weekTotalHours += $week['week_total_hours'];
                }
            }
        }
        return $weekTotalHours;
    }

    private function formatHoursAndMinutes($totalHours)
    {
        $hours = floor($totalHours);
        $minutes = ($totalHours - $hours) * 60;

        $total_hours = "";
        if ($hours > 0) {
            $total_hours .= "$hours " . ($hours === 1 ? "hour" : "hours");
        }
        if ($minutes > 0) {
            if ($total_hours !== "") {
                $total_hours .= " ";
            }
            $total_hours .= "$minutes " . ($minutes === 1 ? "minute" : "minutes");
        }

        return $total_hours;
    }



    public function exportReport(Request $request)
    {
        $startDate = $request->query('starting_date');
        $endDate = $request->query('ending_date');
        $locationId = $request->query('location_id');

        $schedules = Schedule::with(['faculty.subject', 'subject', 'batch.batchSlots', 'slotTime'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($locationId) {
            $schedules->where('location_id', $locationId);
        }

        $schedules = $schedules->get();
        $reportData = $this->buildReportData($schedules);

        $export = new ReportExport($reportData);

        $fileName = 'report.xlsx';

        return Excel::download($export, $fileName);
    }

    public function getFacultiesCount(Request $request){
        
        $startDate = $request->query('starting_date');
        $endDate = $request->query('ending_date');
        $locationId = $request->query('location_id');
    
        $schedules = Schedule::whereBetween('date', [$startDate, $endDate]);
    
        if ($locationId) {
            $schedules->where('location_id', $locationId);
        }
    
        $schedules = $schedules->get();
    
        // Extract unique faculty IDs from the schedules collection
        $uniqueFacultyIds = $schedules->unique('faculty_id')->pluck('faculty_id')->toArray();
    
        // Initialize result array
        $result = [];
    
        // Loop through unique faculty IDs
        foreach ($uniqueFacultyIds as $facultyId) {
            // Get faculty details from the faculties table
            $faculty = Faculty::find($facultyId);
    
            // Count occurrences of the faculty ID in schedules
            $count = $schedules->where('faculty_id', $facultyId)->count();
    
            // Add faculty details and count to result array
            $result[] = [
                'faculty_id' => $faculty->id,
                'faculty_name' => $faculty->first_name . ' ' . $faculty->last_name,
                'count' => $count,
            ];
        }
    
        // Return the result as JSON
        return response()->json(['Faculties' => $result]);
    }
    
}
