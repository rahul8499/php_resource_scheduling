<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Faculty;

class BatchFaculty extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id',
        'batch_id',
        'faculty_id',
       
    ];

    // BatchFaculty.php (BatchFaculty model)
public function faculty()
{
    return $this->belongsTo(Faculty::class, 'faculty_id', 'id');
}

}
