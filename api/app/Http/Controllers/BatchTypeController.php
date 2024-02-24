<?php

namespace App\Http\Controllers;
use App\Models\BatchType;
use Illuminate\Http\Request;

class BatchTypeController extends Controller
{
     Public function Batchtype(Request $req)
    {
        $batchtype=new BatchType();
        $batchtype->name=$req->name;
        $result=$batchtype->save();
        if($result)
        {
            return["result"=>"data has been saved"];
        }
        else
        {
            return["result"=>"failed"];
        }
    }
     function showByIDBatchtype(Request $req, $id)
    {
        $batchtype = BatchType::find($id);
        return $batchtype;
    }
      
    public function getBatchtype(Request $req)
    {
        $requestParam = $req->all();
        $query=BatchType::select('batch_types.*');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_types.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('batch_types.name','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
     public function UpdateBatchtype (Request $req, $id)
    {
       $batchtype = BatchType::find($id);
       $batchtype->name=$req->name;
       $batchtype->update();
        return $batchtype;
    }
      public function DeleteBatchtype ($id)
    { 
        $batchtype = BatchType::find($id);
        $result=$batchtype->delete();
        return["result"=>"data has been daleted"];
    }
    
    
}
