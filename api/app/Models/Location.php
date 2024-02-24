<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
   use HasFactory,SoftDeletes,HasUuids ;
    protected $fillable = ['id', 'name', 'updated_at', 'created_at'];

    function batch ()
    {
    return $this->belongsToMany(Batch::class, 'batches_locations');
    }
    function faculty ()
    {
    return $this->belongsToMany(Faculty::class, 'faculty_locations');
    }
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }

    public function saveFacultyLocation($locationIds,$facultyId){
        //delete faculty_locations where faculty_id=$facultyId
            DB::table('faculty_locations')->where('faculty_id', $facultyId)->delete();

        foreach ($locationIds as $locationId) {
            DB::table('faculty_locations')->insert([
                'id' => Str::uuid()->toString(), // Generate UUID
                'faculty_id' => $facultyId,
                'location_id' => $locationId,
            ]);
        }
    }
    // public function updateFacultyLocation($locationIds,$facultyId){
    //     //delete faculty_locations where faculty_id=$facultyId

    //     foreach ($locationIds as $locationId) {
    //         DB::table('faculty_locations')->insert([
    //             'id' => Str::uuid()->toString(), // Generate UUID
    //             'faculty_id' => $facultyId,
    //             'location_id' => $locationId,
    //         ]);
    //     }
    // }
    

    public function saveLocation($locationIds,$batchId){
        DB::table('batches_locations')->where('batch_id', $batchId)->delete();
        foreach ($locationIds as $locationId) {
            DB::table('batches_locations')->insert([
                'id' => Str::uuid()->toString(),
                'batch_id' => $batchId,
                'location_id' => $locationId,
            ]);
        }
    }

    
}
