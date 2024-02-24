<?php

namespace App\Models;
use App\Models\BatchCode;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\SoftDeletes;


class BatchCode extends Model
{
   use HasFactory,SoftDeletes,HasUuids ;
    protected $fillable = ['id', 'name', 'updated_at', 'created_at'];

    function batch ()
    {
    return $this->belongsToMany(Batch::class, 'batches_batch_codes');
    }

    // public function saveBatchCode($batchCodeIds,$batchId){
    //         DB::table('batches_batch_codes')->where('batch_id', $batchId)->delete();

    // foreach ($batchCodeIds as $batchCodeId) {
    //         DB::table('batches_batch_codes')->insert([
    //             'id' => Str::uuid()->toString(),
    //             'batch_id' => $batchId,
    //             'batch_code_id' => $batchCodeId,
    //         ]);
    //     }
    // }
}

