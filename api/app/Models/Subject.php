<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory,SoftDeletes,HasUuids;
    protected $fillable=[
        'id', 'subject_name', 'updated_at', 'created_at'
    ];
    function batch_stream ()
    {
    return $this->belongsToMany(BatchStream::class, 'batch_stream_subjects');
    }

    function faculty ()
    {
    return $this->belongsToMany(Subject::class, 'faculty_subjects');
    }
    public function schedule()
    {
        return $this->hasOne(Schedule::class);
    }

    public function saveFacultySubject($subjectIds,$facultyId){
            DB::table('faculty_subjects')->where('faculty_id', $facultyId)->delete();

        foreach ($subjectIds as $subjectId) {
            DB::table('faculty_subjects')->insert([
                'id' => Str::uuid()->toString(), 
                'subject_id' => $subjectId,
                'faculty_id' => $facultyId,
            ]);
        }
    }

    public function save_subject($SubjectIds,$batchstreamId){
        DB::table('batch_stream_subjects')->where('batch_stream_id', $batchstreamId)->delete();
        foreach ($SubjectIds as $subjectId) {
            DB::table('batch_stream_subjects')->insert([
                'id' => Str::uuid()->toString(),
                'subject_id' => $subjectId,
                'batch_stream_id' => $batchstreamId,
            ]);
        }
    }
}
