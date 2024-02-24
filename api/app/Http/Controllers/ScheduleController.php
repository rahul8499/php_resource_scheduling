<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Schedule;
use App\Models\Faculty;
use App\Models\Batch;
use App\Models\Location;
use App\Models\Subject;
use App\Models\BatchesLocation;
use App\Models\BatchesBatchSlot;
use App\Models\BatchStreamSubject;
use App\Models\BatchFaculty;
use App\Models\FacultyLocation;
use App\Models\FacultySubject;
use App\Models\Leave;
use App\Models\SlotTime;
use App\Models\BatchStream; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use App\Exports\ScheduleExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserSchedulePublished;
use App\Mail\FacultySchedulePublished;


class ScheduleController extends Controller
{

public function createSchedule(Request $req, $scheduleType = null)
{
    // Extract request data
    $locationId = $req->location_id;
    // $batchStreamId = $req->batch_stream_id;
    $batchId = $req->batch_id;
    $facultyId = $req->faculty_id;
    $subjectId = $req->subject_id;
    $slotTime = $req->slot_time; // Slot time in the format "2:30 am - 3:30 am"
    $date = $req->date;

    if ($facultyId) {
        $faculty = Faculty::find($facultyId);
        if (!$faculty || $faculty->deleted_at !== null) {
            return response()->json(['message' => 'Invalid or inactive faculty_id provided'], 400);
        }
    }
    if ($batchId) {
        $batch = Batch::find($batchId);
        if (!$batch || $batch->deleted_at !== null) {
            return response()->json(['message' => 'Invalid or inactive batch_id provided'], 400);
        }
    }

    // Check if $scheduleType is provided and set the schedule_type column accordingly
    $scheduleType = $scheduleType ?: 'default';
    // Check if the subject is associated with the "Foundation" batch stream
    $isSubjectInFoundationBatch = DB::table('batch_stream_subjects')
        ->join('batch_streams', 'batch_stream_subjects.batch_stream_id', '=', 'batch_streams.id')
        ->where('batch_stream_subjects.subject_id', $subjectId)
        ->where('batch_streams.stream_names', 'Foundation')
        ->exists();
    // echo"isSubjectInFoundationBatch--><pre>";print_r($isSubjectInFoundationBatch);exit;
    if ($scheduleType == "foundation" && !$isSubjectInFoundationBatch) {
        return response()->json(['message' => 'This subject is not allowed for Foundation batch'], 400);
    } elseif ($scheduleType != "foundation" && $isSubjectInFoundationBatch) {
        return response()->json(['message' => 'This subject is not allowed for the selected batch'], 400);
    }
    // Extract only the start time from the slot time
    $startTime = trim(explode('-', $slotTime)[0]);
    // Use the start time to determine "morning" or "afternoon" based on 24-hour format
    $hour = (int)date('H', strtotime($startTime));
    $scheduleSlot = ($hour < 12) ? 'Morning' : 'Afternoon';
    // echo"scheduleSlot-->";print_r($scheduleSlot);exit;
    // Check if faculty is on leave for the specified date
    $leaveData = DB::table('leaves')
        ->where('faculty_id', $facultyId)
        ->where('dates', 'like', '%' . $date . '%')
        ->first();

    if ($leaveData){
        $leaveBatchSlotId = $leaveData->batch_slot_id;
        // Retrieve slot times for the batch slot in leave data
        $leaveBatchSlots = DB::table('batch_slots')
        ->where('id', $leaveBatchSlotId)
        ->pluck('name')
        ->toArray();
        
        // Check if batch slots match the schedule slot
        if (!empty($leaveBatchSlots) && $leaveBatchSlots[0] === $scheduleSlot) {
            return response()->json(['message' => 'Faculty is on leave for this slot and date'], 400);
        }
    }
    $batchesBatchSlots = DB::table('batches_batch_slots')
    ->where('batch_id', $batchId)
    ->whereNull('deleted_at') // Exclude soft-deleted records
    ->get();
        // echo"batchesBatchSlots-->";print_r($batchesBatchSlots);exit;
    if (!$batchesBatchSlots) {
        return response()->json(['message' => 'Slot times not found for this batch'], 400);
    }

    if (!$batchesBatchSlots->isEmpty()) {
        // Extract slot times for each slot type
        $availableSlots = [];
        foreach ($batchesBatchSlots as $record) {
            $slot = $record->slot;
            $slotTimes = json_decode($record->slot_times, true);
            $availableSlots[$slot] = $slotTimes;
        }

        // Check if the provided slot_time is in any of the available slot times
        $providedSlotTime = $req->slot_time;
        $slotFound = false;

        foreach ($availableSlots as $slotType => $slotTimes) {
            if (in_array($providedSlotTime, $slotTimes)) {
                $slotFound = true;
                break;
            }
        }

        if (!$slotFound) {
            $responseMessage = 'Selected slot time is not available for this batch';
            $debugInfo = [
                'batchId' => $batchId,
                'slot' => $scheduleSlot,
                'slotTimes' => $availableSlots,
            ];

            return response()->json(['message' => $responseMessage, 'debug' => $debugInfo], 400);
        }
    } else {
        return response()->json(['message' => 'Slot times not found for this batch'], 400);
    }

    // Check if a schedule already exists for this slot_time and date
    $existingSchedule1 = Schedule::where('slot_time', $slotTime)
        ->where('date', $date)
        ->where('faculty_id',$facultyId)
        ->where('location_id',$locationId)
        ->first();

    $existingSchedule2 = Schedule::where('slot_time', $slotTime)
        ->where('date', $date)
        ->where('batch_id',$batchId)
        ->where('location_id',$locationId)
        ->first();
        
    if ($existingSchedule1) {
        return response()->json(['message' => 'Faculty already assigned for this slot and date'], 400);
    }
    if ($existingSchedule2) {
        return response()->json(['message' => 'Batch already assigned for this slot and date'], 400);
    }

    // Create a new schedule
    $schedule = new Schedule();
    $schedule->location_id = $locationId;
    // $schedule->batch_stream_id = $batchStreamId;
    $schedule->batch_id = $batchId;
    $schedule->faculty_id = $facultyId;
    $schedule->subject_id = $subjectId;
    $schedule->slot_time = $slotTime;
    $schedule->date = $date;
    // $schedule->schedule_type = $scheduleType; // Set the schedule type

    if ($schedule->save()) {
        return response()->json(['message' => 'Schedule has been created', 'data' => $schedule], 201);
    } else {
        return response()->json(['message' => 'Failed to create schedule'], 400);
    }
}

    
public function getSchedule(Request $req)
{
    $startDate = $req->query('starting_date');
    $endDate = $req->query('ending_date');
    $locationId = $req->query('location_id');
    $facultyId = $req->query('faculty_id');

    $schedules = Schedule::with(['faculty' => function ($query) {
        $query->withTrashed(); // Include soft-deleted faculties
    },  'batch' => function ($query) {
        $query->withTrashed(); // Include soft-deleted batches
    }, 'batch.batchSlots', 'batch.batchTypes', 'location', 'subject'])
        ->whereBetween('date', [$startDate, $endDate])
        ->when($locationId, function ($query) use ($locationId) {
            return $query->where('location_id', $locationId);
        })
        ->when($facultyId, function ($query) use ($facultyId) {
            return $query->where('faculty_id', $facultyId);
        })
        ->orderBy('date') //added for date shorting
        ->get();

    if ($schedules->isEmpty()) {
        return response()->json(['message' => 'No schedules found within the specified date range'], 404);
    }

    $storageType = config('filesystems.default');
    $bucketName = '';
    $region = '';

    if ($storageType === 's3') {
        $bucketName = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
    }

    $schedules->transform(function ($schedule) use ($storageType, $bucketName, $region) {
        $faculty = $schedule->faculty;

        if ($faculty) {
            if ($faculty->image && $storageType === 's3') {
                $imagePath = 'faculty_images/' . basename($faculty->image);
                $faculty->image_url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$imagePath}";
            } else {
                $faculty->image_url = $faculty->image ? Storage::disk($storageType)->url($faculty->image) : null;
            }
        }

        // Decode the "slot_times" string into an array
        $schedule->batch->batchSlots->transform(function ($batchSlot) {
            if (is_string($batchSlot->slot_times)) {
                $batchSlot->slot_times = json_decode($batchSlot->slot_times);
            }

        return $batchSlot;
    });
    // Include error attribute if exists
        if (isset($schedule->error)) {
            $schedule->error = [$schedule->error];
        }

        return $schedule;
    });

    return response()->json($schedules);
}


    public function showByIDSchedule(Request $req, $id)
{
    $schedule = Schedule::with(['faculty' => function ($query) {
        $query->withTrashed(); // Include soft-deleted faculties
    }, 'batch' => function ($query) {
        $query->withTrashed(); // Include soft-deleted batches
    },  'batch.batchSlots', 'batch.batchTypes', 'location', 'subject'])->find($id);

    if (!$schedule) {
        return response()->json(['message' => 'Schedule not found'], 404);
    }

    $storageType = config('filesystems.default');
    $bucketName = '';
    $region = '';

    if ($storageType === 's3') {
        $bucketName = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
    }

    $faculty = $schedule->faculty;

    if ($faculty) {
        if ($faculty->image && $storageType === 's3') {
            $imagePath = 'faculty_images/' . basename($faculty->image);
            $faculty->image_url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$imagePath}";
        } else {
            $faculty->image_url = $faculty->image ? Storage::disk($storageType)->url($faculty->image) : null;
        }
    }

    // Decode the "slot_times" string into an array
    $schedule->batch->batchSlots->transform(function ($batchSlot) {
        if (is_string($batchSlot->slot_times)) {
            $batchSlot->slot_times = json_decode($batchSlot->slot_times);
        }

        return $batchSlot;
    });
    // Include error attribute if exists
        if (isset($schedule->error)) {
            $schedule->error = [$schedule->error];
        }

    return response()->json($schedule);
}

    
    public function updateSchedule(Request $req, $scheduleType = null, $id)
{
    
    // Extract request data
    $locationId = $req->location_id;
    $batchId = $req->batch_id;
    $facultyId = $req->faculty_id;
    $subjectId = $req->subject_id;
    $slotTime = $req->slot_time; // Slot time in the format "2:30 am - 3:30 am"
    $date = $req->date;

    // Check if the schedule with the given ID exists
    $existingSchedule = Schedule::find($id);

    if (!$existingSchedule) {
        return response()->json(['message' => 'Schedule not found'], 404);
    }

    if ($facultyId) {
        $faculty = Faculty::find($facultyId);
        if (!$faculty || $faculty->deleted_at !== null) {
            return response()->json(['message' => 'Invalid or inactive faculty_id provided'], 400);
        }
    }
    if ($batchId) {
        $batch = Batch::find($batchId);
        if (!$batch || $batch->deleted_at !== null) {
            return response()->json(['message' => 'Invalid or inactive batch_id provided'], 400);
            //   echo"batch  -->";print_r($batch );exit;
        }
    }

    // Check if $scheduleType is provided and set the schedule_type column accordingly
    $scheduleType = $scheduleType ?: 'default';

    // Check if the subject is associated with the "Foundation" batch stream
    $isSubjectInFoundationBatch = DB::table('batch_stream_subjects')
        ->join('batch_streams', 'batch_stream_subjects.batch_stream_id', '=', 'batch_streams.id')
        ->where('batch_stream_subjects.subject_id', $subjectId)
        ->where('batch_streams.stream_names', 'Foundation')
        ->exists();

    if ($scheduleType == "foundation" && !$isSubjectInFoundationBatch) {
        return response()->json(['message' => 'This subject is not allowed for Foundation batch'], 400);
    } elseif ($scheduleType != "foundation" && $isSubjectInFoundationBatch) {
        return response()->json(['message' => 'This subject is not allowed for the selected batch'], 400);
    }

    // Extract only the start time from the slot time
    $startTime = trim(explode('-', $slotTime)[0]);
    // Use the start time to determine "morning" or "afternoon" based on 24-hour format
    $hour = (int)date('H', strtotime($startTime));
    $scheduleSlot = ($hour < 12) ? 'Morning' : 'Afternoon';
    //  echo"scheduleSlot -->";print_r($scheduleSlot);exit;

    // Check if faculty is on leave for the specified date
    $leaveData = DB::table('leaves')
        ->where('faculty_id', $facultyId)
        ->where('dates', 'like', '%' . $date . '%')
        ->first();

    if ($leaveData) {
        $leaveBatchSlotId = $leaveData->batch_slot_id;
        // Retrieve slot times for the batch slot in leave data
        $leaveBatchSlots = DB::table('batch_slots')
            ->where('id', $leaveBatchSlotId)
            ->pluck('name')
            ->toArray();

        // Check if batch slots match the schedule slot
        if (!empty($leaveBatchSlots) && $leaveBatchSlots[0] === $scheduleSlot) {
            return response()->json(['message' => 'Faculty is on leave for this slot and date'], 400);
        }
    }

    $batchesBatchSlots = DB::table('batches_batch_slots')
        ->where('batch_id', $batchId)
        ->whereNull('deleted_at') // Exclude soft-deleted records
        ->get();
    
    if (!$batchesBatchSlots) {
        return response()->json(['message' => 'Slot times not found for this batch'], 400);
    }

    if (!$batchesBatchSlots->isEmpty()) {
        // Extract slot times for each slot type
        $availableSlots = [];
        foreach ($batchesBatchSlots as $record) {
            $slot = $record->slot;
            $slotTimes = json_decode($record->slot_times, true);
            $availableSlots[$slot] = $slotTimes;
        }

        // Check if the provided slot_time is in any of the available slot times
        $providedSlotTime = $req->slot_time;
        $slotFound = false;

        foreach ($availableSlots as $slotType => $slotTimes) {
            if (in_array($providedSlotTime, $slotTimes)) {
                $slotFound = true;
                break;
            }
        }

        if (!$slotFound) {
            $responseMessage = 'Selected slot time is not available for this batch';
            $debugInfo = [
                'batchId' => $batchId,
                'slot' => $scheduleSlot,
                'slotTimes' => $availableSlots,
            ];

            return response()->json(['message' => $responseMessage, 'debug' => $debugInfo], 400);
        }
    } else {
        return response()->json(['message' => 'Slot times not found for this batch'], 400);
    }

    // Check if a schedule already exists for this slot_time and date
    $existingSchedule1 = Schedule::where('slot_time', $slotTime)
        ->where('date', $date)
        ->where('faculty_id', $facultyId)
        ->where('location_id', $locationId)
        ->where('id', '!=', $id)
        ->first();

    $existingSchedule2 = Schedule::where('slot_time', $slotTime)
        ->where('date', $date)
        ->where('batch_id', $batchId)
        ->where('location_id', $locationId)
        ->where('id', '!=', $id)
        ->first();

    if ($existingSchedule1) {
        return response()->json(['message' => 'Faculty already assigned for this slot and date'], 400);
    }

    if ($existingSchedule2) {
        return response()->json(['message' => 'Batch already assigned for this slot and date'], 400);
    }

    // Update the existing schedule with new data
    $existingSchedule->location_id = $locationId;
    $existingSchedule->batch_id = $batchId;
    $existingSchedule->faculty_id = $facultyId;
    $existingSchedule->subject_id = $subjectId;
    $existingSchedule->slot_time = $slotTime;
    $existingSchedule->date = $date;
    // $existingSchedule->schedule_type = $scheduleType; // Set the schedule type

    if ($existingSchedule->save()) {
        return response()->json(['message' => 'Schedule has been updated', 'data' => $existingSchedule], 200);
    } else {
        return response()->json(['message' => 'Failed to update schedule'], 400);
    }
}


    public function DeleteSchedule($id)
{
    $schedule = Schedule::find($id);

    if (!$schedule) {
        return response()->json(['error' => 'Schedule not found'], 404);
    }

    $schedule->delete();

    return response()->json(['message' => 'Schedule deleted successfully'], 200);
}


public function exportSchedule(Request $request)
{
     ob_start();
    // Get the stream from the URL or request
    $streamCodes = explode('/', $request->input('batch_stream'));
    $locationId = $request->input('location_id');
    $startDate = $request->input('start_date');
    $endDate = $request->input('end_date');

    $allBatchIds = [];

    foreach ($streamCodes as $streamCode) {
    // Find the batch_stream entry based on stream_code
        $batchStreamEntry = DB::table('batch_streams')
            ->where('stream_code', $streamCode)
            ->first();
        // If batchStreamEntry is not found, handle accordingly (return an error, redirect, etc.)
        if (!$batchStreamEntry) {
            return redirect()->back()->with('error', 'Invalid batch_stream specified.');
        }

        // Get the batch_stream_id from the batch_streams table
        $batchStreamId = $batchStreamEntry->id;

        // Find batch_ids associated with the given batch_stream_id
        $batchIds = DB::table('batches_batch_streams')
            ->where('batch_stream_id', $batchStreamId)
            ->pluck('batch_id')
            ->toArray();
        // echo"batchIds-->";print_r($batchIds);exit;
        
        // Merge batchIds for all stream codes
        $allBatchIds = array_merge($allBatchIds, $batchIds);
        // echo"allBatchIds-->";print_r($allBatchIds);exit;
    }
    $activeBatchIds = Batch::whereIn('id', $allBatchIds)->whereNull('deleted_at')->pluck('id')->toArray();

    // Update $facultyIds with active faculty IDs
    $allBatchIds = $activeBatchIds;
    // echo"allBatchIds-->";print_r($allBatchIds);exit;

    // Convert string dates to Carbon instances for comparison
    $startDate = Carbon::parse($startDate);
    $endDate = Carbon::parse($endDate);
    // Query the schedules based on location_id and batch_ids
    $schedules = Schedule::where('location_id', $locationId)
        ->whereIn('batch_id', $allBatchIds)
        ->whereBetween('date', [$startDate, $endDate])
        ->whereNull('deleted_at')
        ->get();
        // echo"schedules-->";print_r($schedules->toArray());exit;
    // Check if there are schedules before exporting
    if ($schedules->isEmpty()) {
        return redirect()->back()->with('error', 'No schedules found for the specified location and stream.');
    }
    $schedules = $schedules->sortBy(function ($schedule) {
    return $schedule->date . $schedule->original_slot_time;
})->values();

    // Export the schedules using Laravel Excel
    return Excel::download(new ScheduleExport($schedules, $locationId, $streamCode), 'schedule.xlsx');
    ob_end_clean();
}


public function autoSchedule(Request $request)
{
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');
    $locationId = $request->input('location_id');

    $facultyLocations = FacultyLocation::where('location_id', $locationId)->get();
    $facultyIds = $facultyLocations->pluck('faculty_id')->toArray();
    // Filter out soft-deleted faculty IDs
    $activeFacultyIds = Faculty::whereIn('id', $facultyIds)->whereNull('deleted_at')->pluck('id')->toArray();

    // Update $facultyIds with active faculty IDs
    $facultyIds = $activeFacultyIds;

    $facultiesData = [];
    foreach ($facultyIds as $facultyId) {
            $faculty = Faculty::find($facultyId);

            $locations = FacultyLocation::where('faculty_id', $facultyId)
                ->join('locations', 'faculty_locations.location_id', '=', 'locations.id')
                ->select('locations.id', 'locations.name','locations.location_code')
                ->get();
            $formattedFacultyLocation = $locations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'code' => $location->location_code,
                    'name' => $location->name,
                ];
            });
            // echo"formattedFacultyLocation--><pre>";print_r($formattedFacultyLocation->toArray());exit;
            $subjects = FacultySubject::where('faculty_id', $facultyId)
                ->join('subjects', 'faculty_subjects.subject_id', '=', 'subjects.id')
                ->select('subjects.id', 'subjects.subject_name', 'subjects.subject_code')
                ->get();

            $formattedFacultySubjects = $subjects->map(function ($subject) {
            $formattedSubject = [
            'id' => $subject->id,
            'name' => $subject->subject_name,
            'code' => $subject->subject_code,
            ];

            // Check if subject_name is "Physics"
            if ($subject->subject_name === 'Physics') {
            $formattedSubject['sessionsPerWeek'] = 4;
            }

            return $formattedSubject;
            })->toArray();

            $leaves = Leave::where('faculty_id', $facultyId)
                ->where(function ($query) use ($fromDate, $toDate) {
                    $query->where(function ($innerQuery) use ($fromDate, $toDate) {
                        $innerQuery->whereRaw("FIND_IN_SET(?, dates) > 0", [$fromDate])
                            ->orWhereRaw("FIND_IN_SET(?, dates) > 0", [$toDate]);
                    })
                    ->orWhere(function ($innerQuery) use ($fromDate, $toDate) {
                        $innerQuery->whereBetween(
                            DB::raw('DATE(STR_TO_DATE(dates, "%Y-%m-%d"))'),
                            [$fromDate, $toDate]
                        );
                    });
                })
                ->select('id', 'dates', 'batch_slot_id','faculty_id')
                ->get();

            $formattedLeaves = [];

            foreach ($leaves as $leave) {
                $dates = explode(',', $leave->dates);

                foreach ($dates as $singleDate) {
                    $formattedLeaves[] = [
                        'id' => $leave->id,
                        'teacher_id' => $leave->faculty_id,
                        'date' => $singleDate,
                        'time_slot' => strtoupper($leave->batchSlot->name),
                    ];
                }
            }

            $preferredSlots = DB::table('faculty_batch_slot')
                ->where('faculty_id', $facultyId)
                ->join('batch_slots', 'faculty_batch_slot.batch_slot_id', '=', 'batch_slots.id')
                ->pluck('batch_slots.name')
                ->toArray();
            $preferredSlots = array_map('strtoupper', $preferredSlots);


            $formattedFaculty = [
                'id' => $faculty->id,
                'name' => $faculty->first_name . ' ' . $faculty->last_name,
                'locations' => $formattedFacultyLocation,
                'subjects' => $formattedFacultySubjects,
                'preferred_slots' => $preferredSlots,
                'leaves' => $formattedLeaves,
            ];

            $facultiesData[] = $formattedFaculty;
    }

    $batchLocations = BatchesLocation::where('location_id', $locationId)->get();
    $batchIds = $batchLocations->pluck('batch_id')->toArray();

    $activeBatchIds = Batch::whereIn('id', $batchIds)->whereNull('deleted_at')->pluck('id')->toArray();

    // Update $facultyIds with active faculty IDs
    $batchIds = $activeBatchIds;


    $batchesData = [];
    foreach ($batchIds as $batchId) {
        $batch = Batch::find($batchId);

        $batchLocations = BatchesLocation::where('batch_id', $batchId)
            ->join('locations', 'batches_locations.location_id', '=', 'locations.id')
            ->select('locations.id', 'locations.name','locations.location_code')
            ->get();
            // echo"batchLocations--><pre>";print_r($batchLocations->toArray());exit;
        $formattedBatchLocations = collect($batchLocations)->map(function ($location) {
            return [
                'id' => $location['id'],
                'code' => $location['location_code'],
                'name' => $location['name'],
            ];
        });
        $batchFaculties = BatchFaculty::where('batch_id', $batchId)
            ->join('faculties', 'batch_faculties.faculty_id', '=', 'faculties.id')
            ->select('faculties.id', 'faculties.first_name', 'faculties.last_name')
            ->get();

        $formattedBatchFaculties = collect($batchFaculties)->map(function ($faculty) {
            return [
                'id' => $faculty['id'],
                'name' => $faculty['first_name'] . ' ' . $faculty['last_name'],
            ];
        });

        $preferredDays = collect($batch->selected_days)->map(function ($day) {
            return strtoupper($day);
        })->toArray();

       $daysPerWeek = $batch->selected_days_count;


        $batchSlots = BatchesBatchSlot::where('batch_id', $batchId)
            ->select('slot', 'slot_times','id')
            ->get();
        // echo"batchSlots-->";print_r($batchSlots->toArray());exit;
        $formattedBatchSlots = [];
        foreach ($batchSlots as $slot) {
            $decodedSlotTimes = json_decode($slot->slot_times, true);
            foreach ($decodedSlotTimes as $index => $timeRange) {
                // Split the time range into "from" and "to" using the "-" delimiter
                $timeParts = explode('-', $timeRange);

                // Format the time values with two digits for hours and minutes
                $formattedFrom = date('H:i', strtotime($timeParts[0]));
                $formattedTo = date('H:i', strtotime($timeParts[1]));

                $formattedBatchSlots[] = [
                    'id'   => $slot->id , // Use the id from batches_batch_slots
                    'slot_category' => strtoupper($slot->slot), // Uppercase slot value
                    'from' => $formattedFrom,
                    'to'   => $formattedTo,
                ];
            }
        }

        // Fetch subjects for the batch using query builder
        $batchStreams = DB::table('batches_batch_streams')
            ->where('batch_id', $batchId)
            ->get();

        $subjectIds = [];
        foreach ($batchStreams as $batchStream) {
            $batchStreamSubjects = DB::table('batch_stream_subjects')
                ->where('batch_stream_id', $batchStream->batch_stream_id)
                ->get();
            $subjectIds = array_merge($subjectIds, $batchStreamSubjects->pluck('subject_id')->toArray());
        }

        // Fetch subject details using the subjectIds
        $batchSubjects = Subject::whereIn('id', $subjectIds)->get();

        $formattedBatchSubjects = $batchSubjects->map(function ($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->subject_name,
                'code' => $subject->subject_code,
                'sessions_per_week'=>3

            ];
        });
        // echo"formattedBatchLocations--><pre>";print_r($formattedBatchLocations->toArray());exit;
        $formattedBatch = [
            'id' => $batch->id,
            'name' => $batch->name,
            'code' => $batch->batch_code,
            'location' => $formattedBatchLocations[0],
            'teachers' => $formattedBatchFaculties,
            'subjects' => $formattedBatchSubjects->toArray(),
            'slots' => $formattedBatchSlots,
            'preferred_days' => $preferredDays,
           'days_per_week' => $daysPerWeek, 
        ];


        $batchesData[] = $formattedBatch;
    }

    $subjects = Subject::all();

    $formattedSubjects = $subjects->map(function ($subject) {
        return [
            'id' => $subject->id,
            'name' => $subject->subject_name,
            'code' => $subject->subject_code,
        ];
    })->toArray();

    $inputLocation = Location::find($locationId);

    $response = [
        'from_date' => $fromDate,
        'to_date' => $toDate,
        'locations' => [
            [
                'id' => $inputLocation->id,
                'code' => $inputLocation->location_code,
                'name' => $inputLocation->name,
            ],
        ],
        'subjects' => $formattedSubjects,
        'teachers' => $facultiesData,
        'batches' => $batchesData,
    ];

     return $response;

}

public function sendAutoScheduleDataToApi(Request $request)
{
    try {
        // Call your autoSchedule method to get the response
        $response = $this->autoSchedule($request);

        // API endpoint on the destination server (demo-one-service-1)
        $apiEndpoint = "http://smart-schedule:8080/schedule"; 

        // Initialize cURL session
        $ch = curl_init($apiEndpoint);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        // Execute cURL session and get the response
        $curlResponse = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        // Check the response from the server
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            // Data transferred successfully
            $saveResult = $this->saveRecords($curlResponse, $request->input('from_date'), $request->input('to_date'), $request->input('location_id'));
            
            if ($saveResult === true) {
                $decodedCurlResponse = json_decode($curlResponse, true);

                return response()->json([
                    'message' => 'Data transferred successfully to API',
                    'response' => $decodedCurlResponse
                ]);
            } else {
                return response()->json([
                    'error' => 'Error saving records',
                    'details' => $saveResult
                ], 500);
            }
        } else {
            // Some error occurred
            return response()->json([
                'error' => 'Error. HTTP Code: ' . $httpCode,
                'response' => json_decode($curlResponse, true)
            ], $httpCode);
        }

        // Close cURL session
        curl_close($ch);
    } catch (\Exception $e) {
        // Handle exceptions (log, return an error response, etc.)
        echo 'Error: ' . $e->getMessage();
    }
}
// Create a new function to save records
private function saveRecords($curlResponse, $from_date, $to_date, $location_id)
{
    try {
        // Decode the JSON response
        $decodedResponse = json_decode($curlResponse, true);

        // Check if decoding was successful
        if ($decodedResponse === null) {
            // Handle the error, e.g., invalid JSON format
            throw new \Exception('Error decoding JSON response');
        }

        // Extract location_id from the locations array
        $locationId = $decodedResponse['locations'][0]['id'];
        // echo"locationId-->";print_r($locationId);exit;

        $existingSchedule=Schedule::where('location_id', $location_id)
                            ->whereBetween('date', [$from_date, $to_date])
                            ->delete();
        // echo"existingSchedule-->";print_r($existingSchedule->toArray());exit;

        // Your existing code to save records based on the API response
        foreach ($decodedResponse['lessons'] as $lesson) {
            $location = Location::where('id', $locationId)->first();
            $batch = Batch::where('id', $lesson['batch']['id'])->first();
            $faculty = Faculty::where('id', $lesson['teacher']['id'])->first();
            $subject = Subject::where('id', $lesson['subject']['id'])->first();

            $slotFrom = date('H:i', strtotime($lesson['slot']['from']));
            $slotTo = date('H:i', strtotime($lesson['slot']['to']));

            $schedule = new Schedule();
            $schedule->id = Str::uuid();
            $schedule->location_id = $location->id;
            $schedule->batch_id = $batch->id;
            $schedule->faculty_id = $faculty->id;
            $schedule->subject_id = $subject->id;
            $schedule->slot_time = $slotFrom . '-' . $slotTo;
            $schedule->date = $lesson['slot']['date'];
            // $schedule->schedule_type = 'default';
            if (isset($lesson['is_error']) && $lesson['is_error']) {
                // If there's an error, save the error message
                $error = implode(", ", $lesson['errors']);
                $schedule->error = $error;
            }

            // Save the record
            $schedule->save();
        }

        // Optionally, return a success message or perform additional actions
        return true;
    } catch (\Exception $e) {
        // Handle exceptions (log, return an error response, etc.)
        return 'Error: ' . $e->getMessage();
    }
}

public function publishSchedule(Request $request)
{
    try {
        // Extract input parameters
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $locationId = $request->input('location_id');
        $locationName = Location::find($locationId)->name;

        // Update the status column to 'publish' for the relevant records
        $updatedRecords = Schedule::where('location_id', $locationId)
            ->whereBetween('date', [$fromDate, $toDate])
            ->update(['status' => 'publish']);

        if ($updatedRecords > 0) {
            // Get the faculty details associated with the published schedules
            $facultyDetails = Schedule::where('location_id', $locationId)
                ->whereBetween('date', [$fromDate, $toDate])
                ->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
                ->join('batches', 'schedules.batch_id', '=', 'batches.id')
                ->get(['schedules.faculty_id', 'schedules.slot_time', 'schedules.date', 'subjects.subject_name','batches.batch_code'])
                ->groupBy('faculty_id');
            // echo"facultyDetails-->";print_r($facultyDetails->toArray());exit;

            // Check if the user is authenticated
            // $user = Auth::user();
            // if ($user) {
                // $userName = $user ? $user->name : null;

                // Format the date for user email
                $formattedFromDate = Carbon::parse($fromDate)->format('d-m-Y');
                $formattedToDate = Carbon::parse($toDate)->format('d-m-Y');

                // Send email to user
                // Mail::to($user->email)
                //     ->send(new UserSchedulePublished($formattedFromDate, $formattedToDate, $locationName, $userName));

                // Send emails to faculties
                $facultySchedules = $facultyDetails->groupBy('faculty_id');
                $sendMail = config('app.send_mail') || env('SEND_MAIL');
                // Send emails to faculties
                if ($sendMail) {
                    foreach ($facultyDetails as $facultyId => $details) {
                        $faculty = Faculty::find($facultyId);
                        // echo"faculty-->";print_r($faculty);exit;
                        $schedules = $details->toArray();
                        // echo"schedules-->";print_r($schedules);exit;
                        // Sort schedules by date and slot_time
                        usort($schedules, function ($a, $b) {
                            $dateComparison = strtotime($a['date']) - strtotime($b['date']);

                            if ($dateComparison === 0) {
                                return strtotime($a['slot_time']) - strtotime($b['slot_time']);
                            }

                            return $dateComparison;
                        });

                        // Format the date and slot time within the loop
                        foreach ($schedules as &$schedule) {
                            // Format date using DateTime
                            $date = \DateTime::createFromFormat('Y-m-d', $schedule['date']);
                            $schedule['date'] = $date ? $date->format('d-m-Y') : 'Invalid Date';

                            // Extract start and end times from the slot_time
                            $timeRange = explode('-', $schedule['slot_time']);
                            $startTime = \DateTime::createFromFormat('H:i', $timeRange[0]);
                            $endTime = \DateTime::createFromFormat('H:i', $timeRange[1]);
                            // Format start and end times
                            $formattedStartTime = $startTime ? $startTime->format('h:i A') : 'Invalid Time';
                            $formattedEndTime = $endTime ? $endTime->format('h:i A') : 'Invalid Time';

                            // Combine formatted start and end times
                            $schedule['slot_time'] = "$formattedStartTime - $formattedEndTime";
                        }
                        
                        Mail::to($faculty->mail)
                            ->send(new FacultySchedulePublished($formattedFromDate, $formattedToDate, $locationName, $faculty->first_name, $schedules));
                    }
                }

                return response()->json(['message' => 'Schedule records published successfully'], 200);
            
        } else {
            return response()->json(['message' => 'No matching records found for publishing'], 404);
        }
    } catch (\Exception $e) {
        // Handle exceptions (log, return an error response, etc.)
        return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
    }
}

public function getSubjectCounts(Request $req)
{
    $startDate = $req->query('starting_date');
    $endDate = $req->query('ending_date');
    $locationId = $req->query('location_id');
    $streamCodes = explode('/', $req->query('stream_code')); // Explode stream_code

    // Fetch all schedules records
    $schedules = Schedule::whereBetween('date', [$startDate, $endDate]);
    if ($locationId) {
        $schedules->where('location_id', $locationId);
    }
    $schedules = $schedules->get();

    $result = [];

    // Iterate over each stream code
    foreach ($streamCodes as $streamCode) {
        // Fetch batch_stream_id based on stream_code
        $batchStreamId = BatchStream::where('stream_code', $streamCode)->value('id');

        // Fetch subject_ids associated with the batch_stream_id
        $subjectIds = BatchStreamSubject::where('batch_stream_id', $batchStreamId)->pluck('subject_id');

        // Fetch subject details for the subject_ids
        $subjects = Subject::whereIn('id', $subjectIds)->get();

        // Initialize result with subjects and count 0
        foreach ($subjects as $subject) {
            if (!isset($result[$subject->id])) {
                $result[$subject->id] = [
                    'subject_id' => $subject->id,
                    'subject_name' => $subject->subject_name,
                    'count' => 0,
                ];
            }
        }
    }

    // Update counts based on schedules
    foreach ($schedules as $schedule) {
        $subjectId = $schedule->subject_id;
        // If the subject is in the result array, increment the count
        if (isset($result[$subjectId])) {
            $result[$subjectId]['count']++;
        }
    }

    // Convert the associative array to a simple array
    $response = ['subjects' => array_values($result)];

    return response()->json($response, 200);
}


}

