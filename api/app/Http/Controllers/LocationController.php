<?php

namespace App\Http\Controllers;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
     Public function location(Request $req)
    {
        $location=new Location();
        $input = $req->name;
        $location->name = $input;

        $location->location_code = strtoupper(substr($input, 0, 1));
        $result=$location->save();
        if($result)
        {
            return["result"=>"data has been saved"];
        }
        else
        {
            return["result"=>"failed"];
        }
    }
     function showByIDlocation(Request $req, $id)
    {
        $location = Location::find($id);
        return $location;
    }
      
    public function getlocation(Request $req)
    {
        $requestParam = $req->all();
        $query=Location::select('locations.*');
        // if(!empty($requestParam))
        // {
        //     $query->Where('locations.name','like',"%".$requestParam['q']."%");
        // }
        $result= $query->orderBy('locations.name','asc')->get();
        // $result=$query->paginate($requestParam['limit']);
        return $result;
    }
    public function updateLocation(Request $req, $id)
{
    $location = Location::find($id);

    if (!$location) {
        return ["result" => "Location not found"];
    }

    $input = $req->name;

    // Update the 'name' field
    $location->name = $input;

    // Update the 'location_code' field
    $location->location_code = strtoupper(substr($input, 0, 1));

    $result = $location->save();

    if ($result) {
        return ["result" => "Location has been updated"];
    } else {
        return ["result" => "Update failed"];
    }
}

      public function Deletelocation($id)
    { 
        $location = Location::find($id);
        $location->delete();
        return $location;
    }
 
}
