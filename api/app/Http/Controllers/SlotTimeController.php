<?php

namespace App\Http\Controllers;
use App\Models\SlotTime;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;


class SlotTimeController extends Controller
{
    Public function createSlotTime(Request $req)
    {
        $SlotTime=new SlotTime();
        $SlotTime->slot_time=$req->slot_time;
        // $batchslottime->batch_slot_id = $req->batch_slot_id;
        $result=$SlotTime->save();
        if($result)
        {
            return["result"=>"data has been saved"];
        }
        else
        {
            return["result"=>"failed"];
        }
    }
     function showByIDSlotTime(Request $req, $id)
    {
        $SlotTime = SlotTime::find($id);
        return $SlotTime;
    }
      
    public function getSlotTime(Request $req)
    {
        $requestParam = $req->all();
        $query=SlotTime::select('slot_times.*');
        // ->with('batch_slot');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_types.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('slot_times.slot_time','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
     public function UpdateSlotTime (Request $req, $id)
    {
       $SlotTime = SlotTime::find($id);
        $SlotTime->batch_slot_time=$req->batch_slot_time;
        // $SlotTime->batch_slot_id = $req->batch_slot_id;
       $SlotTime->update();
        return $SlotTime;
    }
      public function DeleteSlotTime ($id)
    { 
        $SlotTime = SlotTime::find($id);
        $result=$SlotTime->delete();
        return["result"=>"data has been daleted"];
    }
    
    
}


