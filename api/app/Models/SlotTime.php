<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlotTime extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id', 'slot_time', 'updated_at', 'created_at'
    ];

    function batch_slot ()
    {
    return $this->belongsToMany(BatchSlot::class, 'batch_slot_times');
    }
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }

    public function save_slot_time($SlotTimeIds,$batchslotId){
        foreach ($SlotTimeIds as $slottimeId) {
            DB::table('batch_slot_time')->insert([
                'id' => Str::uuid()->toString(), // Generate UUID
                'batch_slot_id' => $batchslotId,
                'slot_time_id' => $slottimeId,
            ]);
        }
    }
}
