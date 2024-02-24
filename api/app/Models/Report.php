<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Location;
use App\Models\Schedule;



class Report extends Model
{
    use HasFactory,HasUuids,SoftDeletes;
   protected $fillable = [
        'location_id',
        'schedule_id',
        'date',
    ];

      public function Schedule()
    {
        return $this->hasMany(Schedule::class);
    }

      public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
