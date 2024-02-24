<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class BatchTypesArray extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
      protected $fillable=[
        'id',
        'faculty_id',
        'batch_type_id',
       
    ];
     function faculty ()
    {
         return $this->hasOne('App\Models\faculty');
     }
      function batchtype ()
     {
         return $this->hasOne('App\Models\BatchType');
     }
}
