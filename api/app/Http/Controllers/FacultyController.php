<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\BatchStream;
use App\Models\BatchStreamSubject;
use Illuminate\Support\Str;
use App\Models\BatchSlot;
use App\Models\Location;
use App\Models\FacultyBatchSlot;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Exports\FacultyExport;
use Maatwebsite\Excel\Facades\Excel;


class FacultyController extends Controller
{
    protected $searchFacultiesArray = ['faculties.first_name', 'faculties.last_name', 'faculties.mail', 'faculties.phone', 'faculties.gender', 'faculties.experience'];

    public function createfaculty(Request $request)
{
    // $postData= $request->all();
    // echo "comes";print_r($postData);exit;
    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'mail' => 'required|email|unique:faculties,mail',
        'phone' => 'required|numeric|min:10',
        'gender' => 'required',
        'age' => 'required|numeric|min:2',
        'address' => 'required|string|max:255',
        'subject_id' => 'required',
        'location_id' => 'required',
        'experience' => 'required',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'faculty_code'=> 'required|string|max:255'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $image = $request->file('image');
    $imagePath = $image ? $this->storeImage($image) : null;

    $faculty = new Faculty();
    $faculty->first_name = $request->first_name;
    $faculty->last_name = $request->last_name;
    $faculty->mail = $request->mail;
    $faculty->phone = $request->phone;
    $faculty->gender = $request->gender;
    $faculty->address = $request->address;
    $faculty->age = $request->age;
    $faculty->experience = $request->experience;
    $faculty->faculty_code = $request->faculty_code;

    $faculty->image = $imagePath;

    if ($faculty->save()) {
        $batchSlotIds = is_array($request->batch_slot_id) ? $request->batch_slot_id : [$request->batch_slot_id];
        $subjectIds = is_array($request->subject_id) ? $request->subject_id : [$request->subject_id];
        $locationIds = is_array($request->location_id) ? $request->location_id : [$request->location_id];
        $facultyId = $faculty->id;

        $BatchSlot = new BatchSlot();
        $BatchSlot->saveFacultySlot($batchSlotIds, $facultyId);

        $Location = new Location();
        $Location->saveFacultyLocation($locationIds, $facultyId);

        $subject = new Subject();
        $subject->saveFacultySubject($subjectIds, $facultyId);

        return response()->json([
            'message' => 'Faculty created successfully',
            'data' => $faculty
        ], 201);
    } else {
        return response()->json(['message' => 'Failed to create faculty'], 500);
    }
}

    public function getFacultyData(Request $request)
{
    $requestParam = $request->all();
    $query = Faculty::select('faculties.*')
        ->with('location')
        ->with('BatchSlot')
        ->with('subject');
    
        if (!empty($requestParam['location_id'])) {
            $query->whereHas('location', function ($locationQuery) use ($requestParam) {
                $locationQuery->where('locations.id', $requestParam['location_id']);
            })->where(function ($query) use ($requestParam) {
                $query->orWhere('faculties.first_name', 'like', '%' . $requestParam['q'] . '%');
                foreach ($this->searchFacultiesArray as $data) {
                    $query->orWhere($data, 'like', "%" . $requestParam['q'] . "%");
                }
                $query->orWhere('faculty_code', 'like', '%' . $requestParam['q'] . '%');
                // Include subject search
                $query->orWhereHas('subject', function ($subjectQuery) use ($requestParam) {
                    $subjectQuery->where('subject_name', 'like', '%' . $requestParam['q'] . '%');
                });
            });
        } 
    // if (!empty($requestParam['first_name'])) {
    //     $query->where('faculties.first_name', 'like', '%' . $requestParam['first_name'] . '%');
    // }
    
    // Fetch faculty based on stream_code
    if (!empty($requestParam['stream_code'])) {
        $streamCodes = explode('/', $requestParam['stream_code']);

        $query->where(function ($query) use ($streamCodes) {
            foreach ($streamCodes as $streamCode) {
                $stream = BatchStream::where('stream_code', $streamCode)->first();

                if ($stream) {
                    $subjectIds = BatchStreamSubject::where('batch_stream_id', $stream->id)->pluck('subject_id')->toArray();

                    // Fetch faculty based on subjects
                    $query->orWhereHas('facultySubjects', function ($facultySubjectQuery) use ($subjectIds) {
                        $facultySubjectQuery->whereIn('faculty_subjects.subject_id', $subjectIds);
                    });
                }
            }
        });
    }

    $query->orderBy("{$requestParam['sortBy']}", "{$requestParam['sortOrder']}");
    $result = $query->paginate($requestParam['limit']);

    // Get the S3 bucket name and region from the configuration
    $storageType = config('filesystems.default');
    $bucketName = '';
    $region = '';

    if ($storageType === 's3') {
        $bucketName = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');
    }

    $result->getCollection()->transform(function ($faculty) use ($storageType, $bucketName, $region) {
        if ($faculty->image && $storageType === 's3') {
            $imagePath = 'faculty_images/' . basename($faculty->image);
            $faculty->image_url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$imagePath}";
        } else {
            $faculty->image_url = $faculty->image ? Storage::disk($storageType)->url($faculty->image) : null;
        }
        return $faculty;
    });

    return $result;
}

    public function showFacultyById($id)
{
    try {
        // Retrieve faculty with associated data
        $faculty = Faculty::with('location')
            ->with('batchSlot.slot_time')
            ->with('subject')
            ->find($id);

        // If faculty is not found, return a 404 response
        if (!$faculty) {
            return response()->json([
                'message' => 'Faculty not found or deleted'
            ], 404);
        }

        // Get the S3 bucket name and region from the configuration
        $storageType = config('filesystems.default');
        $bucketName = '';
        $region = '';

        if ($storageType === 's3') {
            $bucketName = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
        }

        // Prepare the image URL
        if ($faculty->image && $storageType === 's3') {
            $imagePath = 'faculty_images/' . basename($faculty->image);
            $faculty->image_url = "https://{$bucketName}.s3.{$region}.amazonaws.com/{$imagePath}";
        } else {
            $faculty->image_url = $faculty->image ? Storage::disk($storageType)->url($faculty->image) : null;
        }

        // Return faculty data
        return response()->json([
            'message' => 'Faculty found',
            'data' => $faculty
        ], 200);
    } catch (\Exception $e) {
        // If an exception occurs, handle it and return a 500 response
        return response()->json([
            'error' => 'Failed to fetch faculty',
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function updatefaculty(Request $request, $id)
{
    // $postData= $request->all();
    // echo "comes fgfgf";print_r($request->all());exit;
    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'mail' => 'required|email|unique:faculties,mail',
        'phone' => 'required|numeric|min:10',
        'gender' => 'required',
        'age' => 'required|numeric|min:2',
        'address' => 'required|string|max:255',
        'subject_id' => 'required',
        'location_id' => 'required',
        'experience' => 'required',
        'image' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image_url' => 'nullable|string',
        'faculty_code'=> 'required|string|max:255'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $faculty = Faculty::findOrFail($id);

    if (!$faculty) {
        return response()->json(['message' => 'Faculty not found'], 404);
    }

    if ($request->has('image_url')) {
        $faculty->image = $request->input('image_url');
    } else {
        $image = $request->file('image');

        if ($image) {
            // Delete the existing image if it exists
            if ($faculty->image) {
                $this->deleteImage($faculty->image);
            }

            // Store the new image and update the faculty record
            $imagePath = $this->storeImage($image);
            $faculty->image = $imagePath;
        }
    }

    $faculty->first_name = $request->input('first_name');
    $faculty->last_name = $request->input('last_name');
    $faculty->mail = $request->input('mail');
    $faculty->phone = $request->input('phone');
    $faculty->gender = $request->input('gender');
    $faculty->address = $request->input('address');
    $faculty->age = $request->input('age');
    $faculty->experience = $request->input('experience');
    $faculty->faculty_code = $request->input('faculty_code');

    if ($faculty->save()) {
        $batchSlotIds = is_array($request->input('batch_slot_id')) ? $request->input('batch_slot_id') : [$request->input('batch_slot_id')];
        $subjectIds = is_array($request->input('subject_id')) ? $request->input('subject_id') : [$request->input('subject_id')];
        $locationIds = is_array($request->input('location_id')) ? $request->input('location_id') : [$request->input('location_id')];
        $facultyId = $faculty->id;

        $BatchSlot = new BatchSlot();
        $BatchSlot->saveFacultySlot($batchSlotIds, $facultyId);

        $Location = new Location();
        $Location->saveFacultyLocation($locationIds, $facultyId);

        $subject = new Subject();
        $subject->saveFacultySubject($subjectIds, $facultyId);

        return response()->json([
            'message' => 'Faculty updated successfully',
            'data' => $faculty
        ], 200);
    } else {
        return response()->json(['message' => 'Failed to update faculty'], 500);
    }
}


private function storeImage($image)
{
    $storageType = Config::get('filesystems.default');
    $imagePath = '';

    if ($storageType === 'local') {
        $imagePath = $image->store('faculty_images', 'public');
    } elseif ($storageType === 's3') {
        $imagePath = Storage::disk('s3')->put('faculty_images', $image, 'public');
    }
    // Add more conditions for other storage drivers (e.g., 'public', 'spaces', etc.)

    return $imagePath;
}

private function deleteImage($imagePath)
{
    $storageType = Config::get('filesystems.default');

    if ($storageType === 'local') {
        Storage::disk('public')->delete($imagePath);
    } elseif ($storageType === 's3') {
        Storage::disk('s3')->delete($imagePath);
    }
    // Add more conditions for other storage drivers (e.g., 'public', 'spaces', etc.)
}


    public function deletefaculty($id)
{
    $faculty = Faculty::find($id);

    if (!$faculty) {
        return response()->json(['message' => 'Faculty not found'], 404);
    }

    // Delete the faculty record
    if ($faculty->delete()) {

        return response()->json(['message' => 'Faculty deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Failed to delete faculty'], 500);
    }
}

    public function checkFacultyInSchedules($id, Request $request)
{
    $startDate = $request->query('starting_date');
    $endDate = $request->query('ending_date');

    // Check if the faculty ID exists in schedules within the specified date range
    $facultyExistsInSchedules = Schedule::where('faculty_id', $id)
                                        ->whereBetween('date', [$startDate, $endDate])
                                        ->exists();

    if ($facultyExistsInSchedules) {
        return response()->json(['message' => 'Warning: This faculty is currently assigned to active schedules. Deleting it will mark it as inactive, but existing schedules will remain unaffected.',
        'start_date' => $startDate,
        'end_date' => $endDate
        ], 200);
    }else {
        return response()->json(['message' => 'Warning: This faculty is not assigned to active schedules.',
        'start_date' => $startDate,
        'end_date' => $endDate
    ], 404);
    }
}

public function exportFaculty($locationId)
    {
        // $batches = Batch::all(); 
        ob_start();
    $faculties = Faculty::with('location')->get();  
    return Excel::download(new FacultyExport($faculties), 'Faculty.xlsx');
     ob_end_clean();

    }



//     private function storeImage($image)
// {
//     $storageType = config('filesystems.default');
//     $imagePath = '';

//     if ($storageType === 'local') {
//         $imagePath = $image->store('faculty_images', 'public');
//     } elseif ($storageType === 's3') {
//         $imagePath = Storage::disk('s3')->put('faculty_images', $image, 'public');
//     }

//     return $imagePath;
// }
// private function deleteImage($imagePath)
// {
//     $storageType = Config::get('filesystems.default');

//     if ($storageType === 'local') {
//         Storage::disk('public')->delete($imagePath);
//     } elseif ($storageType === 's3') {
//         Storage::disk('s3')->delete($imagePath);
//     }
//     // Add more conditions for other storage drivers (e.g., 'public', 'spaces', etc.)
// }

}