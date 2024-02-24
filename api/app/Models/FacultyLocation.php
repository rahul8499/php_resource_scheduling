<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;


class FacultyLocation extends Model
{
   use HasFactory,SoftDeletes,HasUuids ;
       protected $fillable = ['id', 'faculty_id','location_id'];

}
