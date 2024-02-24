<?php

namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class BachesBatchStream extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id', 'batch_id', 'batch_stream_id','updated_at', 'created_at'
    ];
}
