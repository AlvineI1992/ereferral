<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BloodTypeModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bloodtype'; // your table name
     protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'value',
        'is_active',
        'entryby'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
