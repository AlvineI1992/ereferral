<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class RefEmrModel extends Model
{
    use HasFactory, HasRoles, SoftDeletes;

    protected $table = 'ref_emr';
    protected $primaryKey = 'emr_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true; // Ensures created_at and updated_at work

    protected $fillable = [
        'emr_name',
        'status',
        'remarks',
    ];

    protected $dates = ['deleted_at']; // Soft delete column
}
