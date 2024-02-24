<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlotTimeFoundationController extends Controller
{
    public function getSlotTimesFoundations(Request $request)
    {
        // Retrieve all records from the slot_times_foundations table
        $slotTimesFoundations = DB::table('slot_times_foundations')->get();

        if ($slotTimesFoundations->isEmpty()) {
            return response()->json(['message' => 'No slot times found'], 404);
        }

        // Transform the JSON data in each record to an array
        $slotData = $slotTimesFoundations->map(function ($record) {
            $record->slot_data = json_decode($record->slot_data, true);
            return $record;
        });

        return response()->json(['slot_times_foundations' => $slotData]);
    }
}
