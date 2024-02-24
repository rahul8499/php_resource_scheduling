<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faculty;
use App\Models\Batch;
use App\Models\Location;
use App\Models\Doubt;


class DoubtController extends Controller
{
      public function createDoubt(Request $req)
{
    $doubt = new Doubt();
    $doubt->location_id = $req->location_id;
    $doubt->batch_id = $req->batch_id;
    $doubt->faculty_id = $req->faculty_id;
    $doubt->subject_id = $req->subject_id;
    $doubt->slot_time_id = $req->slot_time_id;

    $result = $doubt->save();
    if ($result) {
        return response()->json(['message' => 'Data has been saved', 'data' => $doubt], 201);
    } else {
        return response()->json(['message' => 'Failed to save data'], 400);
    }
}
     
    public function getDoubt(Request $req)
{
    $startDate = $req->query('starting_date');
    $endDate = $req->query('ending_date');

    $doubt = Doubt::with(['faculty.subject', 'batch', 'batch.batchSlots', 'batch.batchTypes', 'location', 'subject', 'slotTime'])
        ->whereBetween('date', [$startDate, $endDate])
        ->when($locationId, function ($query) use ($locationId) {
            return $query->where('location_id', $locationId);
        })
        ->get();

    return $doubts->isEmpty()
        ? response()->json(['message' => 'No doubts found within the specified date range'], 404)
        : response()->json($doubts);
}

   function showByIDDoubt(Request $req, $id)
    {
        $doubt = Doubt::with(['faculty', 'batch.batchCodes', 'batch', 'batch', 'location', 'subject', 'slotTime'])->find($id);
        return $doubt;
    }
    
    public function UpdateDoubt(Request $req, $id)
{
    $doubt = Doubt::find($id);

    if (!$doubt) {
        return response()->json(['message' => 'doubt not found'], 404);
    }
    $doubt->batch_id = $req->input('batch_id');
    $doubt->location_id = $req->input('location_id');
    $doubt->faculty_id = $req->input('faculty_id');
    $doubt->subject_id = $req->input('subject_id');
    $doubt->slot_time_id = $req->input('slot_time_id');
    $result = $doubt->save();

    if ($result) {
         return response()->json(['message' => 'doubt updated successfully', 'data' => $doubt], 200);
    } else {
        return response()->json(['message' => 'Failed to update doubt'], 500);
    }
}

      public function DeleteDoubt($id)
    { 
        $doubt = Doubt::find($id);
        $doubt->delete();
        return $doubt;
    }


}
