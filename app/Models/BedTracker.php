<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BedTracker extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bed_trackers';

    protected $fillable = [
        'facility_hfhudcode',
        'bed_type',
        'total_beds',
        'occupied_beds',
        'reserved_beds',
        'status',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'occupied_beds' => 'integer',
        'reserved_beds' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function facility()
    {
        return $this->belongsTo(RefFacilitiesModel::class, 'facility_hfhudcode', 'hfhudcode');
    }
}
