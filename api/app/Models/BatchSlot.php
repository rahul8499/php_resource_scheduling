<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Uuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchSlot extends Model
{
   use HasFactory,SoftDeletes,HasUuids ;
    protected $fillable = ['id', 'name', 'slot_time','updated_at', 'created_at'];

    function faculty ()
    {
    return $this->belongsToMany(Faculty::class, 'faculty_batch_slot');
    }

    function batch ()
    {
    return $this->belongsToMany(Batch::class, 'batches_batch_slot');
    }

    function slot_time ()
    {
    return $this->belongsToMany(SlotTime::class, 'batch_slot_time');
    }

    // public function saveSlot($slotTimes,$batchId){
    //         DB::table('batches_batch_slots')->where('batch_id', $batchId)->delete();

    //     // foreach ($slotTimes as $slotTime) {
    //         DB::table('batches_batch_slots')->insert([
    //             'id' => Str::uuid()->toString(),
    //             'batch_id' => $batchId,
    //             'batch_slot' => json_encode($slotTimes),
    //         ]);
    //     // }
    // }
    public function saveFacultySlot($batchSlotIds,$facultyId){
            DB::table('faculty_batch_slot')->where('faculty_id', $facultyId)->delete();

      foreach ($batchSlotIds as $batchSlotId) {
            DB::table('faculty_batch_slot')->insert([
                'id' => Str::uuid()->toString(), // Generate UUID
                'faculty_id' => $facultyId,
                'batch_slot_id' => $batchSlotId,
            ]);
        }
    }
}
