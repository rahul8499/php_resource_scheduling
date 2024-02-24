<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacultyBatchSlot extends Model
{
    use HasFactory,HasUuids,SoftDeletes;
      protected $fillable=[
        'id',
        'faculty_id',
        'batch_slot_id',
       
    ];
    //  function faculty ()
    // {
    //      return $this->hasOne('App\Models\faculty');
    //  }
    //    function FacultyBatchSlot ()
    // {
    //     return $this->hasMany('App\Models\FacultyBatchSlot');
    // }
}
