<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefFacilityModel extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'ref_facilities';

    // Define the primary key
    protected $primaryKey = 'hfhudcode';

    // Disable the automatic incrementing of the primary key since it's a string
    public $incrementing = false;

    // Define the data type of the primary key
    protected $keyType = 'string';

    // Disable timestamps if your table doesn't have created_at/updated_at fields
    public $timestamps = false;

    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'hfhudcode',
        'fhud_seq',
        'facility_name',
        'fhudaddress',
        'region_code',
        'province_code',
        'city_code',
        'bgycode',
        'zip_code',
        'north_coord',
        'east_coord',
        'pres_long_lat',
        'facility_type',
        'status',
        'facility_lto',
        'speed_code',
        'phic_reg_num',
        'dup_fhudcode_1',
        'dup_fhudcode_2',
        'dup_fhudcode_3',
        'dup_fhudcode_4',
        'online_status',
    ];

    // Define any castings for specific columns (optional)
    protected $casts = [
        'region_code' => 'integer',
        'facility_type' => 'integer',
    ];
}
