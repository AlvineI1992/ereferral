<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class RefFacilitiesModel extends Model
{
    use HasFactory;
    use HasRoles;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ref_facilities';

    /**
     * The primary key for the table.
     *
     * @var string
     */
    protected $primaryKey = 'hfhudcode';

    /**
     * Indicates if the primary key is an incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'hfhudcode',
        'facility_name',
        'fhudaddress',
        'status',
        'facility_type',
        'region_code',
        'province_code',
        'city_code',
        'bgycode',
        'remarks',
    ];


   
}
