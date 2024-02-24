<?php

namespace App\Http\Controllers;
use App\Models\Batch;
use App\Models\Schedule;
use App\Models\BatchType;
use App\Models\BatchStream;
use App\Models\Location;
use App\Models\Faculty;
use App\Models\BatchCode;
use Illuminate\Support\Str;
use App\Models\BatchSlot;
use App\Models\BatchesBatchSlot;
use App\Models\SlotTimesFoundation;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Exports\BatchExport;
use Maatwebsite\Excel\Facades\Excel;


class BatchController extends Controller
{ 
    protected $SearchBatchesArray = ['batches.starting_date','batches.duration_type','batches.duration','batch_types.name'];


public function createbatch(Request $request)
{
    $validator = Validator::make($request->all(), [
        'batch_code' => 'required|string|max:255',
        'duration' => 'required|',
        'starting_date' => 'required|date',
        'batch_type_id' => 'required|array',
        'location_id' => 'exists:locations,id',
        'selected_days' => 'array',
        'selected_days_count' => 'integer',
    ]);
   

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    
    // Define the $batch variable
    $batch = new Batch();
    $batch->batch_code = $request->batch_code;
    $batch->duration = $request->duration;
    $batch->duration_type = $request->duration_type;
    $batch->starting_date = $request->starting_date;
   // Set either selected_days or selected_days_count based on input
    if (!is_null($request->selected_days)) {
        $batch->selected_days = $request->selected_days;
        $batch->selected_days_count = null;
    } elseif (!is_null($request->selected_days_count)) {
        $batch->selected_days_count = $request->selected_days_count;
        $batch->selected_days = null;
    }
  // Check if both columns are null
    if (is_null($batch->selected_days) && is_null($batch->selected_days_count)) {
        return response()->json(['message' => 'Either selected_days or selected_days_count must be provided'], 422);
    }


    if ($batch->save()) {
        $facultyIds = $request->has('faculty_id') ? $request->faculty_id : [];

        // If faculty_ids is not an array, convert it to an array
        if (!is_array($facultyIds)) {
            $facultyIds = [$facultyIds];
        }

        // Check validity and activity of each faculty ID
        foreach ($facultyIds as $facultyId) {
            // Validate each faculty_id
            if (!empty($facultyId)) {
                $faculty = Faculty::find($facultyId);
                if (!$faculty || $faculty->deleted_at !== null) {
                    return response()->json(['message' => 'Invalid or inactive faculty_id provided'], 400);
                }
            }
        }
        $batchTypeIds = is_array($request->batch_type_id) ? $request->batch_type_id : [$request->batch_type_id];
        $locationIds = is_array($request->location_id) ? $request->location_id : [$request->location_id];
        $batchStreamId = is_array($request->batch_stream_id) ? $request->batch_stream_id : [$request->batch_stream_id];
        $facultyIds = is_array($request->faculty_id) ? $request->faculty_id : [$request->faculty_id];
        $batchId = $batch->id;

        // Check if the batch_stream_id corresponds to the "foundation" stream
        $foundationStream = BatchStream::where('id', $batchStreamId)->where('stream_names', 'foundation')->first();

            $slotData = $request->slot; // Use the slot data directly from the request

            // Create an array to store the slot data
        foreach ($slotData as $slotItem) {
            if (isset($slotItem['slot']) && isset($slotItem['slot_times'])) {
                // Store all time values for a slot in the same column
                $slotTimesJson = json_encode($slotItem['slot_times']);

                // Create a new entry for each slot
                BatchesBatchSlot::create([
                    'batch_id' => $batchId,
                    'slot' => $slotItem['slot'],
                    'slot_times' => $slotTimesJson,
                ]);
            }
        }

        // Continue with the rest of your code...
        $BatchType = new BatchType();
        $BatchType->saveBatchType($batchTypeIds, $batchId);

        $Location = new Location();
        $Location->saveLocation($locationIds, $batchId);

        $batchstream = new BatchStream();
        $batchstream->savebatchstream($batchStreamId, $batchId);

        $faculty = new faculty();
        $faculty->savefaculty($facultyIds, $batchId);

        return response()->json([
            'message' => 'Batch created successfully',
            'data' => $batch,
        ], 201);
    } else {
        return response()->json(['message' => 'Failed to create batch'], 500);
    }
}

    public function getBatchData(Request $request)
{
    $requestParam = $request->all();

    $query = Batch::select('batches.*')
        ->with([
            'locations',
            'batchSlots', // Update the relationship name
            'batchTypes',
            'batchStream.subject',
            'faculties' => function ($query) { // Include the 'faculties' relationship
                $query->withTrashed();
            },
            'faculties.subject',
            // 'slotTimesFoundations' // Load the relationship
            
        ])
        ->leftJoin('batches_batch_streams', 'batches_batch_streams.batch_id', '=', 'batches.id')
        ->leftJoin('batch_streams', 'batch_streams.id', '=', 'batches_batch_streams.batch_stream_id')
        ->leftJoin('slot_times_foundations', 'slot_times_foundations.batch_id', '=', 'batches.id')
        ->orderBy($requestParam['sortBy'], $requestParam['sortOrder']);

    // Filter by location_id(s) if it's provided in the URL
    if (!empty($requestParam['location_id'])) {
        $locationIds = is_array($requestParam['location_id']) ? $requestParam['location_id'] : [$requestParam['location_id']];
        $query->whereHas('locations', function ($q) use ($locationIds) {
            $q->whereIn('locations.id', $locationIds);
        });
    }
      if (!empty($requestParam['batch_code'])) {
        $query->where('batches.batch_code', 'like', '%' . $requestParam['batch_code'] . '%');
    }

    // Search functionality
    if (!empty($requestParam['q'])) {
        $searchFields = ['batches.starting_date', 'batches.duration_type', 'batches.duration', 'batches.batch_code'];

        $query->where(function ($q) use ($searchFields, $requestParam) {
            foreach ($searchFields as $field) {
                $q->orWhere($field, 'like', '%' . $requestParam['q'] . '%');
            }
        });
    }
    if (isset($requestParam['sortBy']) && isset($requestParam['sortOrder'])) {
        $query->orderBy($requestParam['sortBy'], $requestParam['sortOrder']);
    }
    $result = $query->paginate(isset($requestParam['limit']) && !empty($requestParam['limit'])? $requestParam['limit']: 100);

    // Manually format the response to ensure proper JSON encoding
    $formattedResult = $result->toArray();
    
    if (isset($formattedResult['data']) && is_array($formattedResult['data'])) {
        foreach ($formattedResult['data'] as &$item) {
            if (isset($item['batch_slots']) && is_array($item['batch_slots'])) { // Update the relationship name
                foreach ($item['batch_slots'] as &$batchSlot) {
                    $batchSlot['slot_times'] = json_decode($batchSlot['slot_times']);
                }
            }
        }
    }

    return response()->json($formattedResult);
}

     public function showBatchById($id)
{
    $batch = Batch::select('batches.*')
        ->with([
            'locations',
            'batchSlots',
            'batchTypes',
            'batchStream.subject',
            'faculties' => function ($query) { // Include the 'faculties' relationship
                $query->withTrashed();
            },
            'faculties.subject',
            // 'slotTimesFoundations' // Load the relationship
        ])
        ->where('batches.id', $id)
        ->first();

    if ($batch) {
        // Manually format the response to ensure proper JSON encoding
        $formattedBatch = $batch->toArray();

        if (isset($formattedBatch['batch_slots']) && is_array($formattedBatch['batch_slots'])) {
            foreach ($formattedBatch['batch_slots'] as &$slotTime) {
                $slotTime['slot_times'] = json_decode($slotTime['slot_times']);
            }
        }

        return response()->json([
            'message' => 'Batch found',
            'data' => $formattedBatch
        ], 200);
    } else {
        return response()->json([
            'message' => 'Batch not found'
        ], 404);
    }
}

    public function updatebatch(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
      'batch_code' => 'required|string|max:255',
        'duration' => 'required|',
        'starting_date' => 'required|date',
        'batch_type_id' => 'required|array',
        'location_id' => 'exists:locations,id',
        'selected_days' => 'array',
        'selected_days_count' => 'integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Find the batch by ID
    $batch = Batch::find($id);

    if (!$batch) {
        return response()->json(['message' => 'Batch not found'], 404);
    }

    // Check if the batch_stream_id is changing
$currentBatchStreamId = DB::table('batches_batch_streams')
        ->where('batch_id', $id)
        ->value('batch_stream_id');

    $newBatchStreamId = is_array($request->batch_stream_id) ? $request->batch_stream_id[0] : $request->batch_stream_id;

    // Update batch details
    $batch->batch_code = $request->batch_code;
    $batch->duration = $request->duration;
    $batch->duration_type = $request->duration_type;
    $batch->starting_date = $request->starting_date;
    // Set either selected_days or selected_days_count based on input
    if (!is_null($request->selected_days)) {
        $batch->selected_days = $request->selected_days;
        $batch->selected_days_count = null;
    } elseif (!is_null($request->selected_days_count)) {
        $batch->selected_days_count = $request->selected_days_count;
        $batch->selected_days = null;
    }

    // Check if both columns are null
    if (is_null($batch->selected_days) && is_null($batch->selected_days_count)) {
        return response()->json(['message' => 'Either selected_days or selected_days_count must be provided'], 422);
    }

    if ($batch->save()) {
        $facultyIds = $request->has('faculty_id') ? $request->faculty_id : [];

        // If faculty_ids is not an array, convert it to an array
        if (!is_array($facultyIds)) {
            $facultyIds = [$facultyIds];
        }

        // Check validity and activity of each faculty ID
        foreach ($facultyIds as $facultyId) {
            // Validate each faculty_id
            if (!empty($facultyId)) {
                $faculty = Faculty::find($facultyId);
                if (!$faculty || $faculty->deleted_at !== null) {
                    return response()->json(['message' => 'Invalid or inactive faculty_id provided'], 400);
                }
            }
        }
        $batchTypeIds = is_array($request->batch_type_id) ? $request->batch_type_id : [$request->batch_type_id];
        $locationIds = is_array($request->location_id) ? $request->location_id : [$request->location_id];
        $batchStreamId = is_array($request->batch_stream_id) ? $request->batch_stream_id : [$request->batch_stream_id];
        $facultyIds = is_array($request->faculty_id) ? $request->faculty_id : [$request->faculty_id];
        $batchId = $batch->id;

        // Check if the batch_stream_id corresponds to the "foundation" stream
        $foundationStream = BatchStream::where('id', $batchStreamId)->where('stream_names', 'foundation')->first();
        
        // Delete existing slot information for the batch
        BatchesBatchSlot::where('batch_id', $batchId)->delete();

        $slotData = $request->slot; // Use the slot data directly from the request

        // Create an array to store the slot data
        foreach ($slotData as $slotItem) {
            if (isset($slotItem['slot']) && isset($slotItem['slot_times'])) {
                // Store all time values for a slot in the same column
                $slotTimesJson = json_encode($slotItem['slot_times']);

                // Create a new entry for each slot
                BatchesBatchSlot::create([
                    'batch_id' => $batchId,
                    'slot' => $slotItem['slot'],
                    'slot_times' => $slotTimesJson,
                ]);
            }
        }

        // Continue with the rest of your code...
        $BatchType = new BatchType();
        $BatchType->saveBatchType($batchTypeIds, $batchId);

        $Location = new Location();
        $Location->saveLocation($locationIds, $batchId);

        $batchstream = new BatchStream();
        $batchstream->savebatchstream($batchStreamId, $batchId);

        $faculty = new faculty();
        $faculty->savefaculty($facultyIds, $batchId);

        return response()->json([
            'message' => 'Batch updated successfully',
            'data' => $batch,
        ], 200);
    } else {
        return response()->json(['message' => 'Failed to update batch'], 500);
    }
}   



    public function delete($id)
{        // Find the batch by ID
        $batch = Batch::find($id);

        if (!$batch) {
            return response()->json(['message' => 'Batch not found'], 404);
        }

        if ($batch->delete()) {

        return response()->json(['message' => 'Batch deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Failed to delete Batch'], 500);
    }
}

    public function checkBatchInSchedules($id, Request $request)
{
    $startDate = $request->query('starting_date');
    $endDate = $request->query('ending_date');

    // Check if the faculty ID exists in schedules within the specified date range
    $batchExistsInSchedules = Schedule::where('batch_id', $id)
                                        ->whereBetween('date', [$startDate, $endDate])
                                        ->exists();

    if ($batchExistsInSchedules) {
        return response()->json(['message' => 'Warning: This batch is currently assigned to active schedules. Deleting it will mark it as inactive, but existing schedules will remain unaffected.',
        'start_date' => $startDate,
        'end_date' => $endDate
        ], 200);
    }else {
        return response()->json(['message' => 'Warning: This batch is not assigned to active schedules.',
        'start_date' => $startDate,
        'end_date' => $endDate
    ], 404);
    }
}
   
    // public function batchesList()
    // {
    //     return Batch::select('batches.id', 'batches.batch_code')
    //             ->orderBy('batch_code', 'asc')
    //             ->get();
    // }
    public function exportBatch($locationId)
{
    ob_start();
    $batches = Batch::with(['locations', 'batchTypes', 'BatchSlots', 'batchStream'])->get();

    return Excel::download(new BatchExport($batches), 'batch.xlsx');
   ob_end_clean();
}
}   
