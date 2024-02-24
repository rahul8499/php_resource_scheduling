<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Location;
use App\Models\BatchSlotTime;
use App\Models\BatchStream;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Faculty;
use App\Models\Batch;
use App\Models\Subject;
use App\Models\SlotTime;
use App\Models\BatchCode;
use App\Models\ReportCode;

class Doubt extends Model
{
    use  HasFactory,HasUuids,SoftDeletes;
     protected $fillable = [
        'location_id',
        'batch_id',
        'faculty_id',
        'subject_id',
        'slot_time_id',
        
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function slotTime()
    {
        return $this->belongsTo(SlotTime::class);
    }
    

}
