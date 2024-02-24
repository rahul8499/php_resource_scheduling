<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchType extends Model
{
    use HasFactory,SoftDeletes,HasUuids ;
    protected $fillable=[
        'id', 'name', 'updated_at', 'created_at'
    ];
    function batch ()
    {
    return $this->belongsToMany(Batch::class, 'batches_batch_types');
    }

    public function saveBatchType($batchTypeIds,$batchId){
            DB::table('batches_batch_types')->where('batch_id', $batchId)->delete();

    foreach ($batchTypeIds as $batchTypeId) {
            DB::table('batches_batch_types')->insert([
                'id' => Str::uuid()->toString(),
                'batch_id' => $batchId,
                'batch_type_id' => $batchTypeId,
            ]);
        }
    }
}
