<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchStream extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id', 'stream_names', 'updated_at', 'created_at'
    ];

    function batch ()
    {
    return $this->belongsToMany(Batch::class, 'batches_batch_streams');
    }
    function subject ()
    {
    return $this->belongsToMany(Subject::class, 'batch_stream_subjects');
    }

    public function savebatchstream($batchStreamIds,$batchId){
            DB::table('batches_batch_streams')->where('batch_id', $batchId)->delete();

    foreach ($batchStreamIds as $batchStreamId) {
            DB::table('batches_batch_streams')->insert([
                'id' => Str::uuid()->toString(),
                'batch_id' => $batchId,
                'batch_stream_id' => $batchStreamId,
            ]);
        }
    }
    
}