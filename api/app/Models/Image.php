<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Image extends Model
{
    use HasFactory,SoftDeletes,HasUuids;

    protected $fillable = [ 'name', 'path'];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}

