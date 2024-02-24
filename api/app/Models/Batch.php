<?php

namespace App\Models;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BatchSlotTime;
use App\Models\BatchStream;
use App\Models\BatchSlot;
use App\Models\BatchCode;
use App\Models\BatchFaculty;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;



class Batch extends Model
{
    use HasFactory, SoftDeletes, HasUuids ;
    
     protected $casts =[
         'id',
    // Other fillable fields
    'duration',
    'duration_type',
    'starting_date',
     'selected_days_count',
      'selected_days' => 'array',
    ];

     function batchSlotTime ()
    {
        return $this->hasMany('App\Models\BatchSlotTime');
    }
    function BatchSlots ()
    {
    return $this->hasMany(BatchesBatchSlot::class, 'batch_id');
    }
     function batchTypes ()
    {
        return $this->belongsToMany(BatchType::class, 'batches_batch_types');
    }
     function locations ()
    {
        return $this->belongsToMany(Location::class, 'batches_locations');
    }
    // function batchCodes ()
    // {
    //     return $this->belongsToMany(BatchCode::class, 'batches_batch_codes');
    // }
    // function batchStream ()
    // {
    //     return $this->belongsToMany(BatchStream::class, 'batches_batch_streams');
    // }
    function batchStream ()
    {
        return $this->belongsToMany(BatchStream::class, 'batches_batch_streams');
    }
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }
    // public function slotTimesFoundations()
    // {
    //     return $this->hasMany(SlotTimesFoundation::class, 'batch_id');
    // }
    // Batch.php (Batch model)
public function faculties()
{
    return $this->hasManyThrough(
        Faculty::class,
        BatchFaculty::class,
        'batch_id', // Foreign key on BatchFaculty table referencing batches
        'id', // Primary key on Faculty table
        'id', // Primary key on Batches table
        'faculty_id' // Foreign key on BatchFaculty table referencing faculty
    );
}

    
}
