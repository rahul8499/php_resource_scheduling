<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchesBatchType extends Model
{
    use HasFactory;
    protected $fillable=[
        'id',
        'batch_id',
        'batch_type_id',
       
    ];
}
