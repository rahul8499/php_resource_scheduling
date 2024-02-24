<?php

namespace App\Models;
namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use App\Models\BatchStream;
use App\Models\Subject;
use Illuminate\Http\Request;

class BatchStreamController extends Controller
{
    
     public function Batchstream(Request $req)
{
    $batchstream = new BatchStream();
    $input = $req->stream_names;
    $batchstream->stream_names = $input;
    $batchstream->stream_code = strtoupper(substr($input, 0, 1));
    $result = $batchstream->save();

    if ($batchstream->save()) {
        $SubjectIds = is_array($req->subject_id) ? $req->subject_id : [$req->subject_id];
        $batchstreamId = $batchstream->id;

        $subject = new Subject();
        $subject->save_subject($SubjectIds, $batchstreamId);

        return response()->json([
            'message' => 'batchstream created successfully',
            'data' => $batchstream
        ], 201);
    } else {
        return response()->json(['message' => 'Failed to create batchstream'], 500);
    }
}


     function showByIDBatchStream(Request $req, $id)
    {
        $batchstream = BatchStream::find($id);
        return $batchstream;
    }
      
    public function getBatchStream(Request $req)
    {
        $requestParam = $req->all();
        $query=BatchStream::select('batch_streams.*')
            ->with('subject');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_types.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('batch_streams.stream_names','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
     public function UpdateBatchStream (Request $req, $id)
    {
       $batchstream = BatchStream::find($id);
       $batchstream->name=$req->name;
       $batchstream->update();
        return $batchstream;
    }
      public function DeleteBatchStream ($id)
    { 
        $batchstream = BatchStream::find($id);
        $result=$batchstream->delete();
        return["result"=>"data has been daleted"];
    }
    
    
}