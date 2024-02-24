<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\BatchSlot;
use App\Models\Faculty;
use App\Models\FacultyLocation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Exports\LeaveExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;



class LeaveController extends Controller
{

    public function createLeave(Request $request){
        try {
            $inputData = $request->all();
            $facultyId = $inputData['faculty_id'];
            $startingDate = $inputData['starting_date'];
            $endingDate = $inputData['ending_date'];
            $batchSlotIds = $inputData['batch_slot_ids'];

            if ($facultyId) {
                $faculty = Faculty::find($facultyId);
                if (!$faculty || $faculty->deleted_at !== null) {
                    return response()->json(['message' => 'Invalid or inactive faculty_id provided'], 400);
                }
            }

            // Calculate dates between starting_date and ending_date
            $dates = \Carbon\CarbonPeriod::create($startingDate, $endingDate)->toArray();

            // Iterate through each batch_slot_id
            foreach ($batchSlotIds as $batchSlotId) {
                // Check if the faculty is already on leave on any of the specified dates with the same batch_slot_id
                $existingLeave = Leave::where('faculty_id', $facultyId)
                    ->where('batch_slot_id', $batchSlotId)
                    ->where(function ($query) use ($dates) {
                        foreach ($dates as $date) {
                            $query->orWhere('dates', 'like', '%' . $date->format('Y-m-d') . '%');
                        }
                    })
                    ->first();

                if ($existingLeave) {
                    // Return error response if faculty is already on leave on the specified date
                    return response()->json(['error' => 'Faculty is already on leave on the specified date with the same batch slot'], 400);
                }

                // Convert dates to a comma-separated string
                $datesString = implode(',', array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $dates));

                // Create one record for each batch_slot_id with the entire date range
                Leave::create([
                    'faculty_id' => $facultyId,
                    'dates' => $datesString,
                    'batch_slot_id' => $batchSlotId,
                ]);
            }

            // Return success response
            return response()->json(['message' => 'Leaves created successfully'], 201);
        } catch (\Exception $e) {
            // Return error response on exception
            return response()->json(['error' => 'Failed to create leaves', 'message' => $e->getMessage()], 500);
        }
    }

// private function getBatchSlotsInfo($batchSlotIds)
// {
//     // Assuming you have a BatchSlot model for the batch_slots table
//     return BatchSlot::whereIn('id', $batchSlotIds)->get(['id', 'name']);
// }

    public function getLeave(Request $request) {
        try {
            $facultyId = $request->input('faculty_id');
            $locationId = $request->input('location_id');
            $startingDate = $request->input('starting_date');
            $endingDate = $request->input('ending_date');
            $facultySearch = $request->input('faculty');
            $page = $request->input('page', 1); // Default to page 1 if not provided
            $limit = $request->input('limit', 10);
            $sortBy = $request->input('sort_by');
            $sortOrder = $request->input('sort_order', 'asc');

            // Build query to retrieve leave records
            $query = Leave::with(['faculty' => function ($query) {
                $query->withTrashed(); // Include soft-deleted faculties
            },'batchSlot']);

            //Optionally filter by location_id if provided
            if ($locationId) {
                // Use a subquery to filter faculties based on location_id
                $query->whereIn('faculty_id', function ($subquery) use ($locationId) {
                    $subquery->select('faculty_id')
                        ->from('faculty_locations')
                        ->where('location_id', $locationId);
                });
            }

            // Add date range filter if starting_date and ending_date are provided
            if ($startingDate && $endingDate) {
                $query->where(function ($subquery) use ($startingDate, $endingDate) {
                    $subquery->where(function ($innerQuery) use ($startingDate, $endingDate) {
                        $innerQuery->whereRaw("FIND_IN_SET(?, dates) > 0", [$startingDate])
                            ->orWhereRaw("FIND_IN_SET(?, dates) > 0", [$endingDate]);
                    })
                    ->orWhere(function ($innerQuery) use ($startingDate, $endingDate) {
                        $innerQuery->whereBetween(
                            DB::raw('DATE(STR_TO_DATE(dates, "%Y-%m-%d"))'),
                            [$startingDate, $endingDate]
                        );
                    });
                });
            }

            // Optionally filter by faculty search if provided
            if ($facultySearch) {
                $query->whereIn('faculty_id', function ($subquery) use ($facultySearch) {
                    $subquery->select('id')
                        ->from('faculties')
                        ->where('first_name', 'like', '%' . $facultySearch . '%')
                        ->orWhere('faculty_code', 'like', '%' . $facultySearch . '%');
                });
            }

            // Add sorting if sort_by is provided
            // Add sorting if sort_by is provided
if ($sortBy) {
    if ($sortBy === 'starting_date' || $sortBy === 'ending_date') {
            $dateColumn = $sortBy === 'starting_date' ? 'SUBSTRING_INDEX(dates, ",", 1)' : 'SUBSTRING_INDEX(dates, ",", -1)';
            $query->orderByRaw("CAST($dateColumn AS DATE) $sortOrder");
    } elseif ($sortBy === 'faculty') {
        // Sorting is requested for faculties table based on first_name and last_name
        $query->join('faculties', 'leaves.faculty_id', '=', 'faculties.id')
              ->orderBy('faculties.first_name', $sortOrder)
              ->orderBy('faculties.last_name', $sortOrder);
        // Add other faculty fields as needed
    } elseif ($sortBy === 'batch_slot') {
        // Sorting is requested for batch_slots table based on name
        $query->join('batch_slots', 'leaves.batch_slot_id', '=', 'batch_slots.id')
              ->orderBy('batch_slots.name', $sortOrder);
        // Add other batch_slots fields as needed
    } else {
        // Sorting is requested for leaves table
        $query->orderBy($sortBy, $sortOrder);
    }
}


            // Retrieve leave records
            $leaveRecords = $query->paginate($limit, ['*'], 'page', $page);

            // Convert dates from comma-separated string to an array
            $leaveRecords->getCollection()->transform(function ($record) {
                $record->dates = explode(',', $record->dates);
                return $record;
            });

            // Check if there are any leave records
            if ($leaveRecords->isEmpty()) {
                // Return specific message based on conditions
                if ($locationId && $facultySearch) {
                    return response()->json(['message' => 'No leave records found for the specified location and faculty search'], 404);
                } elseif ($locationId) {
                    return response()->json(['message' => 'No leave records found for the specified location'], 404);
                } elseif ($facultySearch) {
                    return response()->json(['message' => 'No leave records found for the specified faculty search'], 404);
                } else {
                    return response()->json(['message' => 'No leave records found'], 404);
                }
            }

            // Return leave records
            return response()->json(['leave_records' => $leaveRecords], 200);
        } catch (\Exception $e) {
            // Return error response on exception
            return response()->json(['error' => 'Failed to retrieve leave records', 'message' => $e->getMessage()], 500);
        }
    }


    public function updateLeave(Request $request, $leaveId){
    try {
        // Find the leave record by its ID
        $leave = Leave::find($leaveId);

        // Check if the leave record exists
        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }

        // Get the input data from the request
        $inputData = $request->all();
        $facultyId = $leave->faculty_id;
        

        // Ensure batch_slot_ids is an array
        $batchSlotIds = $inputData['batch_slot_ids'] ?? [];

        // Calculate dates between starting_date and ending_date
        $dates = \Carbon\CarbonPeriod::create($inputData['starting_date'], $inputData['ending_date'])->toArray();

        // Check for conflicts for each batch slot ID
        $conflictDetected = false;
        $conflictingLeaveRecord = null;

        foreach ($batchSlotIds as $batchSlotId) {
            $conflictingLeave = Leave::where('faculty_id', $facultyId)
                ->where(function ($query) use ($batchSlotId, $dates) {
                    foreach ($dates as $date) {
                        $query->orWhere('dates', 'like', '%' . $date->format('Y-m-d') . '%')
                              ->where('batch_slot_id', 'like', '%' . $batchSlotId . '%');
                    }
                })
                ->where('id', '<>', $leave->id) // Exclude the current leave record from the check
                ->exists();

            if ($conflictingLeave) {
                $conflictDetected = true;
                $conflictingLeaveRecord = Leave::where('faculty_id', $facultyId)
                    ->where(function ($query) use ($dates, $batchSlotIds) {
                        foreach ($dates as $date) {
                            $query->orWhere('dates', 'like', '%' . $date->format('Y-m-d') . '%')
                                  ->whereIn('batch_slot_id', $batchSlotIds);
                        }
                    })
                    ->where('id', '<>', $leave->id)
                    ->first();
                break; // Exit the loop if a conflict is detected
            }
        }

        // If there is no conflict and there are multiple batch_slot_ids, update the existing record
        // and create new records for each batch_slot_id
        if (!$conflictDetected && count($batchSlotIds) > 1) {
            // Update the existing record with the first batch_slot_id
            $leave->update([
                'dates' => implode(',', array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $dates)),
                'batch_slot_id' => $batchSlotIds[0], // Update with the first batch slot ID
            ]);

            // Create new records for the remaining batch_slot_ids
            foreach (array_slice($batchSlotIds, 1) as $additionalBatchSlotId) {
                Leave::create([
                    'faculty_id' => $facultyId,
                    'dates' => implode(',', array_map(function ($date) {
                        return $date->format('Y-m-d');
                    }, $dates)),
                    'batch_slot_id' => $additionalBatchSlotId,
                ]);
            }
        } elseif (!$conflictDetected && count($batchSlotIds) === 1) {
            // If there is no conflict and only one batch_slot_id, update the existing record
            $leave->update([
                'dates' => implode(',', array_map(function ($date) {
                    return $date->format('Y-m-d');
                }, $dates)),
                'batch_slot_id' => $batchSlotIds[0], // Update with the single batch slot ID
            ]);
        } else {
            // Return error response with details of the conflicting leave record
            $conflictingLeaveRecord = Leave::with('batchSlot') // Eager load the relationship
                ->find($conflictingLeaveRecord->id);

            return response()->json([
                'error' => 'Conflict with existing leave records. Please choose a different date or batch slot.',
                'conflicting_leave' => [
                    'id' => $conflictingLeaveRecord->id,
                    'faculty_id' => $conflictingLeaveRecord->faculty_id,
                    'dates' => $datesArray = explode(',', $conflictingLeaveRecord->dates),
                    'batch_slot_id' => $conflictingLeaveRecord->batch_slot_id,
                    'batch_slot_name' => $conflictingLeaveRecord->batchSlot->name, // Access the relationship
                    'created_at' => $conflictingLeaveRecord->created_at,
                    'updated_at' => $conflictingLeaveRecord->updated_at,
                    'deleted_at' => $conflictingLeaveRecord->deleted_at,
                ],
            ], 400);
        }

        // Return success response
        return response()->json(['message' => 'Leave updated successfully'], 200);
    } catch (\Exception $e) {
        // Return error response on exception
        return response()->json(['error' => 'Failed to update leave', 'message' => $e->getMessage()], 500);
    }
}


    public function getById(Request $request, $facultyId) {
    try {
        $startingDate = $request->input('starting_date');
        $endingDate = $request->input('ending_date');
        $sortBy = $request->input('sort_by', 'dates');
        $sortOrder = $request->input('sort_order', 'asc');

        // Build query to retrieve leave records for the specified faculty ID
        $query = Leave::query()->with('batchSlot', 'faculty')->where('faculty_id', $facultyId)->withTrashed();

        // Add date range filter if starting_date and ending_date are provided
        if ($startingDate && $endingDate) {
            $query->where(function ($subquery) use ($startingDate, $endingDate) {
                $subquery->where(function ($innerQuery) use ($startingDate, $endingDate) {
                    $innerQuery->whereRaw("FIND_IN_SET(?, dates) > 0", [$startingDate])
                        ->orWhereRaw("FIND_IN_SET(?, dates) > 0", [$endingDate]);
                })
                ->orWhere(function ($innerQuery) use ($startingDate, $endingDate) {
                    $innerQuery->whereBetween(
                        DB::raw('DATE(STR_TO_DATE(dates, "%Y-%m-%d"))'),
                        [$startingDate, $endingDate]
                    );
                });
            });
        }

        // Add sorting if sort_by is provided
        $query->orderBy($sortBy, $sortOrder);

        // Retrieve leave records
        $leaveRecords = $query->get();

        // Check if there are any leave records
        if ($leaveRecords->isEmpty()) {
            return response()->json(['message' => 'No leave records found for the specified faculty ID'], 404);
        }

        // Convert dates from comma-separated string to an array for each record
        $leaveRecords->each(function ($record) {
            $record->dates = explode(',', $record->dates);
        });

        // Return leave records
        return response()->json(['leave_records' => $leaveRecords], 200);

    } catch (\Exception $e) {
        // Return error response on exception
        return response()->json(['error' => 'Failed to retrieve leave records', 'message' => $e->getMessage()], 500);
    }
}

    public function leaveGetById($id)
{
    try {
        $leaveRecord = Leave::with('batchSlot', 'faculty')->withTrashed()->find($id);

        if ($leaveRecord === null) {
            return response()->json(['message' => 'No leave record found for the specified ID'], 404);
        }

        // Convert dates from comma-separated string to an array
        $leaveRecord->dates = explode(',', $leaveRecord->dates);

        return response()->json([
            'message' => 'Leave retrieved successfully',
            'data' => $leaveRecord
        ], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'Leave not found'], 404);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to retrieve leave', 'error' => $e->getMessage()], 500);
    }
}

    public function deleteLeave($leaveId) {
    try {
        // Find the leave record by its ID
        $leave = Leave::find($leaveId);

        // Check if the leave record exists
        if (!$leave) {
            return response()->json(['error' => 'Leave not found'], 404);
        }

        // Delete the leave record
        $leave->delete();

        // Return success response
        return response()->json(['message' => 'Leave deleted successfully'], 200);
    } catch (\Exception $e) {
        // Return error response on exception
        return response()->json(['error' => 'Failed to delete leave', 'message' => $e->getMessage()], 500);
    }
}

public function exportLeave(Request $request)
{
     ob_start();
    $locationId = $request->query('locationId');
    // Get faculty IDs for the given location
    $facultyIds = DB::table('faculty_locations')
        ->where('location_id', $locationId)
        ->pluck('faculty_id')
        ->toArray();

    // Get leaves for the selected faculties
    $leaves = Leave::whereIn('faculty_id', $facultyIds)->get();

    return Excel::download(new LeaveExport($leaves), 'leave.xlsx');
     ob_end_clean();
}

}
