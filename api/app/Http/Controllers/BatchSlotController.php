<?php

namespace App\Http\Controllers;
use App\Models\BatchSlot;
use App\Models\Faculty;
use App\Models\User;
use App\Models\SlotTime;
use Illuminate\Http\Request;
class BatchSlotController extends Controller
{
     public function Batchslot(Request $req)
{
    $batchslot = new BatchSlot();
    $batchslot->name = $req->name;
    // $batchslot->name = $req->name;
    
    if ($batchslot->save()) {
        $SlotTimeIds = is_array($req->slot_time_id) ? $req->slot_time_id : [$req->slot_time_id];
        $batchslotId = $batchslot->id;

        $Slot_time = new SlotTime();
        $Slot_time->save_slot_time($SlotTimeIds, $batchslotId);

        return response()->json([
            'message' => 'batchslot created successfully',
            'data' => $batchslot
        ], 201);
    } else {
        return response()->json(['message' => 'Failed to create batchslot'], 500);
    }
}

     function showByIDBatchslot(Request $req, $id)
    {
        $batchslot = BatchSlot::find($id);
        return $batchslot;
    }
      
    public function getBatchslot(Request $req)
    {
        $requestParam = $req->all();
        $query=BatchSlot::select('batch_slots.*')
            ->with('slot_time');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_slots.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('batch_slots.name','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
     public function UpdateBatchslot (Request $req, $id)
    {
       $batchslot = BatchSlot::find($id);
       $batchslot->name=$req->name;
       $batchslot->update();
        return $batchslot;
    }
      public function DeleteBatchslot ($id)
    { 
        $batchslot = BatchSlot::find($id);
        $result=$batchslot->delete();
        return["result"=>"data has been daleted"];
    }
    
    
}
