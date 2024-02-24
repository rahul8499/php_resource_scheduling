<?php

namespace App\Http\Controllers;
use App\Models\Subject;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;


class SubjectController extends Controller
{
    Public function createSubject(Request $req)
    {
        $subject=new Subject();
        $input = $req->subject_name;
        $subject->subject_name = $input;
        
        $subject->subject_code = strtoupper(substr($input, 0, 1));
        $result = $subject->save();
        if($result)
        {
            return["result"=>"data has been saved"];
        }
        else
        {
            return["result"=>"failed"];
        }
    }
     function showByIDSubject(Request $req, $id)
    {
        $subject = Subject::find($id);
        return $subject;
    }
      
    public function getSubject(Request $req)
    {
        $requestParam = $req->all();
        $query=Subject::select('subjects.*');
        // ->with('batch_slot');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_types.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('subjects.subject_name','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
     public function UpdateSubject (Request $req, $id)
    {
       $subject = Subject::find($id);
        $subject->subject_name=$req->subject_name;
        // $SlotTime->batch_slot_id = $req->batch_slot_id;
       $subject->update();
        return $subject;
    }
      public function DeleteSubject ($id)
    { 
        $subject = Subject::find($id);
        $result=$subject->delete();
        return["result"=>"data has been daleted"];
    }
    
    
}


