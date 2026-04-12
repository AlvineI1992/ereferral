<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'patient_master_list';

    protected $fillable = [
        'legacy_log_id',
        'family_id',
        'phic_number',
        'case_number',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'birth_date',
        'sex',
        'contact_number',
        'religion',
        'blood_type',
        'blood_type_rh',
        'civil_status',
        'street_address',
        'barangay_code',
        'city_code',
        'province_code',
        'region_code',
        'zip_code',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'deleted_at' => 'datetime',
    ];
}
