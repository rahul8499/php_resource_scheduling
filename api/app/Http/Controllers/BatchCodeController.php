<?php

namespace App\Http\Controllers;
use App\Models\BatchCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BatchCodeController extends Controller
{
    Public function Batchdata(Request $request)
    {
       
        try {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|',
            
        ]);

        if ($validator->fails()) {
            // Log::debug('in if.');
            // Return a validation error response
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create a new resource
        // $resource = BatchCode::create($request->all());

            
        } catch (\Exception $e)
        {
            // Log the exception
            // \Log::error('Error creating resource: ' . $e->getMessage());

      
            return response()->json([
                'message' => 'Error creating resource',
                'error' => 'An unexpected error occurred',
            ], 500);
        }
            $batchcode=new BatchCode();
            $batchcode->name=$request->name;
            $result=$batchcode->save();
            if($result)
            {
                return["result"=>"data has been saved"];
            }
            else
            {
                return["result"=>"failed"];
            }

    }
     function showByIDBatch(Request $request, $id)
    {
        $batchcode = BatchCode::find($id);
        return $batchcode;
    }
      
    public function getBatchdata(Request $request)
    {
        $requestParam = $request->all();
        $query=BatchCode::select('batch_codes.*');
        // if(!empty($requestParam))
        // {
        //     $query->Where('batch_codes.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('batch_codes.name','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }

    
     public  function UpdateBatch (Request $request, $id)
    {
         try {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            
        ]);

        // Update the resource
        $resource = BatchCode::findOrFail($id);
        $resource->update($validatedData);

        // Return a success response
        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => $resource,
        ]);
    } catch (ValidationException $e) {
        // Return a validation error response
        return response()->json([
            'message' => 'Validation error',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        // Log the exception
        \Log::error('Error updating resource: ' . $e->getMessage());

        // Return an error response
        return response()->json([
            'message' => 'Error updating resource',
            'error' => 'An unexpected error occurred',
        ], 500);
    }
        $batchcode = BatchCode::find($id);
        $batchcode->name=$request->name;
        $batchcode->update();
        return $batchcode;
    }



      public function DeleteBatch($id)
    { 
        try {
        // Find the resource by ID
        $resource = BatchCode::findOrFail($id);

        // Delete the resource
        $resource->delete();

        // Return a success response
        return response()->json([
            'message' => 'Resource deleted successfully',
        ]);
    } catch (\Exception $e) {
        // Log the exception
        \Log::error('Error deleting resource: ' . $e->getMessage());

        // Return an error response
        return response()->json([
            'message' => 'Error deleting resource',
            'error' => 'An unexpected error occurred',
        ], 500);
    }
        $batchcode = BatchCode::find($id);
        $batchcode->delete();
        return $batchcode;
    }
    // to do testing
}
