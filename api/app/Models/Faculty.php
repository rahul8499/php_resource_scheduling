<?php

namespace App\Models;
use App\Models\BatchSlot;
use App\Models\BatchFaculty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Models\Uuid;
use App\Models\Faculty;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class Faculty extends Model
{
    use HasFactory,SoftDeletes,HasUuids;

      protected $fillable=[
        'first_name',
        'last_name',
        'mail',
        'phone',
        'address',
        'age',
        'subject',
        'location_id',
        'batch_type_id',
        'image'
        
    ];

    
      public function getSubjectAttribute($value)
    {
      return explode(',', $value);
    }

      function BatchSlot ()
    {
    return $this->belongsToMany(BatchSlot::class, 'faculty_batch_slot');
    }
    //  function FacultyBatchSlot ()
    // {
    //     return $this->hasMany('App\Models\FacultyBatchSlot');
    // }
    function subject ()
    {
    return $this->belongsToMany(Subject::class, 'faculty_subjects');
    }
     function location ()
    {
    return $this->belongsToMany(Location::class, 'faculty_locations');
    }
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }
    public function facultySubjects()
    {
        return $this->hasMany(FacultySubject::class, 'faculty_id');
    }

    public function savefaculty($facultyIds,$batchId){
            DB::table('batch_faculties')->where('batch_id', $batchId)->delete();
        // echo"sms---><pre>";print_r($facultyIds);exit;
    foreach ($facultyIds as $facultyId) {
            DB::table('batch_faculties')->insert([
                'id' => Str::uuid()->toString(),
                'batch_id' => $batchId,
                'faculty_id' => $facultyId,
            ]);
        }
    }
}
