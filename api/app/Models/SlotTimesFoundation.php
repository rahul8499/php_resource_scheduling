<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotTimesFoundation extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id', 'batch_id', 'slot_data',
    ];
}
