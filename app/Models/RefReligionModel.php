<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefReligionModel extends Model
{
    use SoftDeletes;

    protected $table = 'ref_religion';

    protected $primaryKey = 'relcode';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'relcode',
        'reldesc',
        'relstat',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
