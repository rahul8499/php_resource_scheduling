<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Faculty;

class Leave extends Model
{
     use HasFactory, SoftDeletes, HasUuids ;
     protected $fillable = [
        'faculty_id',
        'dates',
        'batch_slot_id',
    ];
    

     public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }
    public function batchSlot()
    {
        return $this->belongsTo(BatchSlot::class, 'batch_slot_id');
    }
}
