<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class ReferralPatientDemoModel extends Model
{
    use HasFactory; use HasRoles;

    protected $table = 'referral_patientdemo';
    protected $primaryKey = 'LogID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'LogID',
        'patientStreetAddress',
        'patientBrgyCode',
        'patientMundCode',
        'patientProvCode',
        'patientRegCode',
        'patientZipCode',
    ];

   
}
