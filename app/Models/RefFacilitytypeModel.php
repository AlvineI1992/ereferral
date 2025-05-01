<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefFacilitytypeModel extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'ref_facilitytype';

    // Define the primary key
    protected $primaryKey = 'factype_code';

    // Disable the automatic incrementing of the primary key since it's a string
    public $incrementing = false;

    // Define the data type of the primary key
    protected $keyType = 'string';

    // Disable timestamps if your table doesn't have created_at/updated_at fields
    public $timestamps = false;

    // Define the fillable attributes for mass assignment
    protected $fillable = [
        'factype_code',
        'description',
        'facility_name'
    ];

    
}
